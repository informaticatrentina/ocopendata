<?php

namespace Opencontent\Opendata\Api;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;

class EnvironmentSettings
{
    protected $identifier;

    protected $defaultParentNodeId;

    protected $multimediaParentNodeId;

    protected $fileParentNodeId;

    protected $imageParentNodeId;

    protected $remoteIdPrefix;

    protected $mapDataInMetadata = array();

    protected $mapMetadataInData = array();

    protected $attributeConverters = array(
        'ezuser' => '\Opencontent\Opendata\Api\AttributeConverter\User',
        'ezpage' => '\Opencontent\Opendata\Api\AttributeConverter\BlackListed'
    );

    protected $relationsParentNodeIdMap = array();

    protected $validatorTolerance;

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
}