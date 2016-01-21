<?php

namespace Opencontent\Opendata\Api\Values;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;

class Metadata
{
    public $id;

    public $remoteId;

    public $classIdentifier;

    public $languages;

    public $name;

    public $ownerId;

    public $nodeIds;

    public $parentNodeIds;

    public $sectionIdentifier;

    public $statusIdentifiers;

    public $published;

    public $modified;

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
        return $this;
    }

}