<?php

namespace Opencontent\Opendata\Api\Values;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;

class Content
{
    /**
     * @var Metadata
     */
    public $metadata;

    /**
     * @var ContentData
     */
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

    public function jsonSerialize()
    {
        return array(
            'metadata' => $this->metadata->jsonSerialize(),
            'data' => $this->data->jsonSerialize()
        );
    }
}