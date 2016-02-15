<?php

namespace Opencontent\Opendata\Api\Structs;


use Opencontent\Opendata\Api\AttributeConverterLoader;
use Opencontent\Opendata\Api\Exception\CreateContentException as Exception;

class ContentDataCreateStruct extends \ArrayObject
{

    protected $data = array();

    protected $validData = array();

    public function __construct( array $array = array() )
    {
        $this->data = $array;
    }

    public function getIterator()
    {
        return new \ArrayIterator( $this->data );
    }

    public function count()
    {
        return count( $this->data );
    }

    public static function __set_state( array $array )
    {
        $object = new static();
        foreach( $array as $key => $value )
        {
            $object->{$key} = $value;
        }
        return $object;
    }

    public function offsetExists( $offset )
    {
        return isset( $this->data[$offset] );
    }

    public function offsetGet( $offset )
    {
        return $this->data[$offset];
    }

    public function offsetSet( $offset, $value )
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset( $offset )
    {
        unset( $this->data[$offset] );
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function validate( MetadataCreateStruct $metadata )
    {
        if ( $metadata->useDefaultLanguage() )
        {
            $language = $metadata->languages[0];
            if ( !isset( $this->data[$language] ) ) {
                $this->data = array($language => $this->data);
            }
        }

        $contentClass = $metadata->getClass()->getClassObject();
        $contentClassIdentifier = $contentClass->attribute( 'identifier' );

        /** @var \eZContentClassAttribute[] $attributes */
        $attributes = $contentClass->dataMap();
        foreach( $attributes as $attribute )
        {
            $identifier = $attribute->attribute( 'identifier' );
            $dataType = $attribute->attribute( 'data_type_string' );
            $isRequired = (bool)$attribute->attribute( 'is_required' );
            $converter = AttributeConverterLoader::load(
                $contentClassIdentifier,
                $identifier,
                $dataType
            );
            foreach( $metadata->languages as $language )
            {
                $dataTranslation = $this->data[$language];
                if ( isset( $dataTranslation[$identifier] ) )
                {
                    $converter->validate( $identifier, $dataTranslation[$identifier], $attribute );
                }
                elseif( $isRequired )
                {
                    throw new Exception( "Field $identifier is required" );
                }
            }
        }
    }

}