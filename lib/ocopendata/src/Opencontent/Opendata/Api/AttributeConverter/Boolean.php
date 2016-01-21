<?php

namespace Opencontent\Opendata\Api\AttributeConverter;
use eZContentObjectAttribute;

class Boolean extends Base
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


    public function type()
    {
        return array( 'identifier' => 'boolean' );
    }
}