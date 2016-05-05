<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\PublicationProcess;

class Price extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        if ( $attribute instanceof eZContentObjectAttribute
             && $attribute->hasContent()
        )
        {
            /** @var \eZPrice $price */
            $price = $attribute->content();
            $content['content'] = array(
                'value' => $price->attribute( 'price' ),
                'vat_id' => $price->attribute( 'id' ),
                'is_vat_included' => (int)$price->attribute( 'is_vat_included' )
            );
        }

        return $content;
    }

    public function set( $data, PublicationProcess $process )
    {
        return $data['value'] . '|' . $data['vat_id'] . '|' . $data['is_vat_included'];
    }

    public static function validate($identifier, $data, eZContentClassAttribute $attribute)
    {
        //@todo
    }

    public function type( eZContentClassAttribute $attribute )
    {
        return array(
            'identifier' => 'price',
            'format' => array(
                'value' => 'integer',
                'vat_id' => 'integer',
                'is_vat_included' => 'boolean'
            )
        );
    }

    public function toCSVString($content, $params = null)
    {
        if (is_array($content) && isset( $content['value'] )) {
            return $content['value'];
        }

        return '';
    }
}