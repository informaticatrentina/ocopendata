<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use SQLIContentUtils;

class EzXml extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        $content['content'] = str_replace( '&nbsp;', ' ', $attribute->content()->attribute( 'output' )->attribute( 'output_text' ) );
        return $content;
    }

    public function set( $data )
    {
        return SQLIContentUtils::getRichContent( $data );
    }

    public function type( eZContentClassAttribute $attribute )
    {
        return array( 'identifier' => 'html' );
    }
}