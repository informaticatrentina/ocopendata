<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;

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

    /**
     * @var eZContentObjectAttribute|\eZContentClassAttribute
     */
    protected $attribute;

    public function __construct(
        $classIdentifier,
        $identifier,
        $attribute
    )
    {
        $this->classIdentifier = $classIdentifier;
        $this->identifier = $identifier;
        $this->attribute = $attribute;
    }

    public function isPublic()
    {
        return true;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getValue()
    {
        if ( $this->attribute instanceof eZContentObjectAttribute
             && $this->attribute->hasContent() )
            return $this->attribute->toString();
        return null;
    }

    public function setValue( $data )
    {
        return $data;
    }

    public function help()
    {
        return null;
    }
}