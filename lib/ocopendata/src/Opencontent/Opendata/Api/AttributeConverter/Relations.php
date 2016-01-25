<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\ContentRepository;
use Opencontent\Opendata\Api\EnvironmentSettings;
use Opencontent\Opendata\Api\Exception\InvalidInputException;
use Opencontent\Opendata\Api\Gateway\FileSystem;
use Opencontent\Opendata\Api\PublicationProcess;
use Opencontent\Opendata\Api\Values\Content;

class Relations extends Base
{

    /**
     * @var FileSystem
     */
    protected static $gateway;

    protected static function gateway()
    {
        if ( self::$gateway === null )
            self::$gateway = new FileSystem();

        return self::$gateway;
    }

    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        $ids = explode( '-', $attribute->toString() );
        $contents = array();
        foreach( $ids as $id )
        {
            try
            {
                $apiContent = self::gateway()->loadContent( $id );
                $contents[] = $this->filterSubContent( $apiContent );
            }
            catch( \Exception $e )
            {
                \eZDebug::writeError( $e->getMessage() );
            }
        }
        $content['content'] = $contents;
        return $content;
    }

    protected function filterSubContent( Content $content )
    {
        $data = array();
        $parentNodes = array();
        foreach( $content->metadata->parentNodes as $parentNode )
        {
            $parentNodes[] = $parentNode['id'];
        }

        $data['metadata'] = array(
            'id' => $content->metadata->id,
            'remoteId' => $content->metadata->remoteId,
            'classIdentifier' => $content->metadata->classIdentifier,
            'languages' => $content->metadata->languages,
            'name' => $content->metadata->name
        );
        return $data;
    }

    public function set( $data, PublicationProcess $process )
    {
        return null; //@todo
        return parent::set( $data, $process );
    }

    public static function validate( $identifier, $data, eZContentClassAttribute $attribute )
    {
        if ( !is_array( $data ) )
        {
            foreach( $data as $item )
            {
                if ( is_array( $item ) )
                {
                    if ( isset( $data['image'] ) )
                        Image::validate( $identifier, $data, $attribute );
                    elseif ( isset( $data['file'] ) )
                        File::validate( $identifier, $data, $attribute );
                    else
                        throw new InvalidInputException( 'Invalid input', $identifier, array( $item ) );
                }
                else
                {
                    try
                    {
                        self::gateway()->loadContent( $item );
                    }
                    catch( \Exception $e )
                    {
                        throw new InvalidInputException( 'Invalid content identifier', $identifier, array( $item ) );
                    }
                }
            }
        }
        throw new InvalidInputException( 'Invalid data', $identifier, $data );
    }

    protected static function findContents( $data )
    {
        $result = array(
            'images' => array(),
            'files' => array(),
            'ids' => array()
        );
        foreach( $data as $item )
        {
            if ( is_array( $item ) )
            {
                if ( isset( $data['image'] ) )
                    $result['images'][] = $item;
                elseif ( isset( $data['file'] ) )
                    $result['files'][] = $item;
            }
            else
            {
                $content = self::gateway()->loadContent( $item );
                $result['ids'][] = $content->metadata->id;
            }
        }
        return $result;
    }

    public function type( eZContentClassAttribute $attribute )
    {
        return array(
            'identifier' => 'array of id or remoteId or file or image'
        );
    }
}