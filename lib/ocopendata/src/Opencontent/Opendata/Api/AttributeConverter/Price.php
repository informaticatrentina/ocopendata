<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\PublicationProcess;

class Price extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        if ( $attribute instanceof eZContentObjectAttribute
             && $attribute->hasContent()
        )
        {
        }

        return $content;
    }

    public function set( $data, PublicationProcess $process )
    {
        return date( "U", strtotime( $data ) );
    }

    public function type( eZContentClassAttribute $attribute )
    {
        return array(
            'identifier' => 'price',
            'format' => array(
                'value' => 'integer',
                'vat_id' => 'integer',
                'is_vat_included' => 'boolean'
            )
        );
    }

    public static function validate( $identifier, $data, eZContentClassAttribute $attribute )
    {
        if ( is_array( $data ) )
        {
            if ( !isset( $data['filename'] ) )
            {
                throw new InvalidInputException( 'Missing filename', $identifier, $data );
            }

            if ( isset( $data['url'] ) && !eZHTTPTool::getDataByURL( trim( $data['url'] ), true ) )
            {
                throw new InvalidInputException( 'Url not responding', $identifier, $data );
            }

            if ( isset( $data['file'] )
                 && !( base64_encode( base64_decode( $data['file'], true ) ) === $data['file'] )
            )
            {
                throw new InvalidInputException( 'Invalid base64 encoding', $identifier, $data );
            }

            if ( !isset( $data['url'] ) )
            {
                $data['url'] = null;
            }

            if ( !isset( $data['file'] ) )
            {
                $data['file'] = null;
            }

        }
        throw new InvalidInputException( 'Invalid data format', $identifier, $data );
    }

    public function toCSVString($content, $params = null)
    {
        if (is_array($content) && isset( $content['value'] )) {
            return $content['value'];
        }

        return '';
    }
}