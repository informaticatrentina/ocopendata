<?php

namespace Opencontent\Opendata\Api;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\AttributeConverter\Base;
use Opencontent\Opendata\Api\Exception\EnvironmentMisconfigurationException;

class AttributeConverterLoader
{

    /**
     * @param $classIdentifier
     * @param $identifier
     * @param eZContentObjectAttribute|eZContentClassAttribute $attribute
     *
     * @return Base
     * @throws EnvironmentMisconfigurationException
     */
    final public static function load(
        $classIdentifier,
        $identifier,
        $attribute
    )
    {
        $className = '\Opencontent\Opendata\Api\AttributeConverter\Base';
        $dataTypeString = $attribute->attribute( 'data_type_string' );
        $converters = (array)self::attributeConverters();
        if ( isset( $converters["{$classIdentifier}/{$identifier}"] ) )
        {
            $className = $converters["{$classIdentifier}/{$identifier}"];
        }
        elseif ( isset( $converters[$identifier] ) )
        {
            $className = $converters[$identifier];
        }
        elseif ( isset( $converters[$dataTypeString] ) )
        {
            $className = $converters[$dataTypeString];
        }
        if ( class_exists( $className ) )
        {
            return new $className(
                $classIdentifier,
                $identifier,
                $attribute
            );
        }
        throw new \Exception( "{$className} not found" );
    }

    public static function attributeConverters()
    {
        return array(
            'ezuser' => '\Opencontent\Opendata\Api\AttributeConverter\User',
            'ezpage' => '\Opencontent\Opendata\Api\AttributeConverter\BlackListed'
        );
    }

}