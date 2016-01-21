<?php

use Opencontent\Opendata\Api\EnvironmentSettings;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\Values\ContentData;

class DefaultEnvironmentSettings extends EnvironmentSettings
{
    public function filterContent( Content $content )
    {
        $content = $this->flatData( $content );
        return parent::filterContent( $content );
    }

    public function flatData( Content $content )
    {
        $flatData = array();
        foreach( $content->data as $language => $data )
        {
            foreach( $data as $identifier => $value )
            {
                $flatData[$language][$identifier] = $value['content'];
            }
        }
        $content->data = new ContentData( $flatData );
        return $content;
    }
}