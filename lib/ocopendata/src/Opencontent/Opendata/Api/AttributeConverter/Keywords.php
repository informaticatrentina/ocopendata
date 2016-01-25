<?php

namespace Opencontent\Opendata\Api\AttributeConverter;


use eZContentClassAttribute;
use eZContentObjectAttribute;
use Opencontent\Opendata\Api\Exception\InvalidInputException;
use Opencontent\Opendata\Api\PublicationProcess;

class Keywords extends Base
{

    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        /** @var \eZKeyword $attributeContent */
        $attributeContent = $attribute->content();
        $content['content'] = $attributeContent->KeywordArray;
        return $content;
    }

    public function set( $data, PublicationProcess $process )
    {
        $data = implode( ', ', $data );
        return parent::set( $data, $process );
    }

    public static function validate( $identifier, $data, eZContentClassAttribute $attribute )
    {
        if ( !is_array( $data ) )
        {
            throw new InvalidInputException( 'Invalid data', $identifier, $data );
        }
    }
}