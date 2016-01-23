<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;

class Date extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        $date = $content['content'];
        $content['content'] = ( (int)$date > 0 ) ? date( 'c', $date ) : null;
        return $content;
    }

    public function set( $data )
    {
        return date( "U", strtotime( $data ) );
    }

    public function type( eZContentClassAttribute $attribute )
    {
        return array( 'identifier' => 'ISO 8601 date' );
    }
}