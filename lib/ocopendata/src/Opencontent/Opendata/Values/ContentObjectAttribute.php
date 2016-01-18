<?php

namespace OpenContent\Opendata\Values;


class ContentObjectAttribute
{
    protected $id;

    protected $contentobject_id;

    protected $version;

    protected $language_code;

    protected $language_id;

    protected $contentclassattribute_id;

    protected $attribute_original_id;

    protected $sort_key_int;

    protected $sort_key_string;

    protected $data_type_string;

    protected $data_text;

    protected $data_int;

    protected $data_float;

    public function __construct( array $properties = array() )
    {
        foreach ( $properties as $property => $value )
        {
            $this->$property = $value;
        }
    }

    public function __set( $property, $value )
    {
        if ( property_exists( $this, $property ) )
        {
            throw new \Exception( $property );
        }
        throw new \Exception( $property );
    }

    public function __get( $property )
    {
        if ( property_exists( $this, $property ) )
        {
            return $this->$property;
        }
        throw new \Exception( $property );
    }

    public function __isset( $property )
    {
        return property_exists( $this, $property );
    }

    public function __unset( $property )
    {
        $this->__set( $property, null );
    }

    public static function __set_state( array $array )
    {
        return new static( $array );
    }
}