<?php

namespace Opencontent\Opendata\Api\Structs;

use Opencontent\Opendata\Api\Exception\OutOfRangeException;

class ContentCreateStruct implements \ArrayAccess
{
    /**
     * @var MetadataCreateStruct
     */
    public $metadata;

    /**
     * @var ContentDataCreateStruct
     */
    public $data;

    public function __construct(MetadataCreateStruct $metadata, ContentDataCreateStruct $data)
    {
        $this->metadata = $metadata;
        $this->data = $data;
    }

    public function validate()
    {
        $this->metadata->validate();
        $this->data->validate( $this->metadata );
    }

    public static function fromArray(array $array)
    {
        $metadata = array();
        if (isset( $array['metadata'] )) {
            $metadata = $array['metadata'];
        }
        $data = array();
        if (isset( $array['data'] )) {
            $data = $array['data'];
        }

        return new self(
            new MetadataCreateStruct($metadata),
            new ContentDataCreateStruct($data)
        );
    }

    public function checkAccess( \eZUser $user )
    {
        $this->metadata->checkAccess();
    }

    public function offsetExists($property)
    {
        return isset( $this->{$property} );
    }

    public function offsetGet($property)
    {
        return $this->{$property};
    }

    public function offsetSet($property, $value)
    {
        if ( property_exists( $this, $property ) )
            $this->{$property} = $value;
        else
            throw new OutOfRangeException( $property );
    }

    public function offsetUnset($property)
    {
        $this->{$property} = null;
    }

}