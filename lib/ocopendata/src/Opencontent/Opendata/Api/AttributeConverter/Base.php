<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\Exception\InvalidInputException;

class Base
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $classIdentifier;


    public function __construct(
        $classIdentifier,
        $identifier
    )
    {
        $this->classIdentifier = $classIdentifier;
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param eZContentObjectAttribute $attribute
     *
     * @return array|string|int|null|\JsonSerializable
     */
    public function get( eZContentObjectAttribute $attribute )
    {
        $data = array(
            'id' => intval( $attribute->attribute( 'id' ) ),
            'version' => intval( $attribute->attribute( 'version' ) ),
            'identifier' => $this->classIdentifier . '/' . $this->identifier,
            'datatype' => $attribute->attribute( 'data_type_string' ),
            'content' => $attribute->hasContent() ? $attribute->toString() : null
        );

        return $data;
    }

    public function set( $data )
    {
        return $data;
    }

    public function validate( $data )
    {
        if ( !is_string( $data ) )
            throw new InvalidInputException( 'Invalid type', $this->getIdentifier(), $data );
    }

    public function help( eZContentClassAttribute $attribute )
    {
        return $attribute->attribute( 'description' );
    }

    public function type()
    {
        return array( 'identifier' => 'string' );
    }
}