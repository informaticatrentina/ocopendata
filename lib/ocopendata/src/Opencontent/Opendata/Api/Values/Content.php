<?php

namespace Opencontent\Opendata\Api\Values;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;

class Content
{
    public $metadata;

    public $data;

    public function __construct( array $properties = array() )
    {
        foreach ( $properties as $property => $value )
        {
            if ( property_exists( $this, $property ) )
                $this->$property = $value;
            else
                throw new OutOfRangeException( $property );
        }
    }

    public static function __set_state( array $array )
    {
        return new static( $array );
    }
}