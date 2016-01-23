<?php

namespace Opencontent\Opendata\Api;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;
use Opencontent\Opendata\Api\Values\Content;

class EnvironmentSettings
{
    protected $identifier;

    protected $defaultParentNodeId;

    protected $multimediaParentNodeId;

    protected $fileParentNodeId;

    protected $imageParentNodeId;

    protected $remoteIdPrefix;

    protected $validatorTolerance;

    protected $debug;

    protected $maxSearchLimit = 100;

    public function __construct( array $properties = array() )
    {
        foreach ( $properties as $property => $value )
        {
            if ( property_exists( $this, $property ) )
            {
                $this->$property = $value;
            }
            else
            {
                throw new OutOfRangeException( $property );
            }
        }
    }

    public function __get( $property )
    {
        if ( property_exists( $this, $property ) )
        {
            return $this->{$property};
        }
        throw new OutOfRangeException( $property );
    }

    public function __set( $property, $value )
    {
        if ( property_exists( $this, $property ) )
        {
            $this->{$property} = $value;
        }
        else
        {
            throw new OutOfRangeException( $property );
        }
    }

    public static function __set_state( array $array )
    {
        return new static( $array );
    }

    public function filterContent( Content $content )
    {
        return $content;
    }
}