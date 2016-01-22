<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;

class Date extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        $content['content'] = (int)$content['content'];
        return $content;
    }

    public function set( $data )
    {
        return (int) $data;
    }

    public function type( eZContentClassAttribute $attribute )
    {
        return array( 'identifier' => 'timestamp' );
    }
}