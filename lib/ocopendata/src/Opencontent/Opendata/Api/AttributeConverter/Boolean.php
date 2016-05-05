<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use Opencontent\Opendata\Api\PublicationProcess;

class Boolean extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        $content['content'] = (int)$content['content'];
        return $content;
    }

    public function set( $data, PublicationProcess $process )
    {
        return (int) $data;
    }

    public function type( eZContentClassAttribute $attribute )
    {
        return array( 'identifier' => 'boolean' );
    }

    public function toCSVString($content, $params = null)
    {
        return $content;
    }
}