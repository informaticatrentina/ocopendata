<?php

namespace OpenContent\Opendata\Values;


class ContentObject
{
    protected $id;

    protected $section_id;

    protected $owner_id;

    protected $contentclass_id;

    protected $name;

    protected $published;

    protected $modified;

    protected $current_version;

    protected $status;

    protected $remote_id;

    protected $language_mask;

    protected $initial_language_id;

    /**
     * @var ContentObjectAttribute[]
     */
    protected $dataMap;

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