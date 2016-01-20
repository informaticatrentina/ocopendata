<?php

namespace Opencontent\Opendata\Api\Values;

class ContentData
{
    public function __construct( array $properties = array() )
    {
        foreach ( $properties as $property => $value )
        {
            if ( is_array( $value ) )
                $value = new static( $value );
            $this->{$property} = $value;
        }
    }

    public static function __set_state( array $properties )
    {
        $data = array();
        foreach ( $properties as $property => $value )
        {
            $data[$property] = $value;
        }
        return new static( $data );
    }
}