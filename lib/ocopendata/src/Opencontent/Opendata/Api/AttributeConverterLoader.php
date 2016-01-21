<?php

namespace Opencontent\Opendata\Api;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\AttributeConverter\Base;

class AttributeConverterLoader
{

    /**
     * @param $classIdentifier
     * @param $identifier
     * @param eZContentObjectAttribute|eZContentClassAttribute $attribute
     *
     * @return \Opencontent\Opendata\Api\AttributeConverter\Base
     * @throws \Exception
     */
    final public static function load(
        $classIdentifier,
        $identifier,
        $dataTypeString
    )
    {
        $className = null;
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
        elseif ( isset( $converters['*'] ) )
        {
            $className = $converters['*'];
        }
        if ( class_exists( $className ) )
        {
            return new $className(
                $classIdentifier,
                $identifier
            );
        }
        else
        {
            return new Base(
                $classIdentifier,
                $identifier
            );
        }
    }

    public static function attributeConverters()
    {
        return array(
            'ezpage' => '\Opencontent\Opendata\Api\AttributeConverter\Page',
            'ezboolean' => '\Opencontent\Opendata\Api\AttributeConverter\Boolean',
            'ezuser' => '\Opencontent\Opendata\Api\AttributeConverter\User'
        );
    }

}