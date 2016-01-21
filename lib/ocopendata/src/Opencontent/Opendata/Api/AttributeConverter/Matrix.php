<?php

namespace Opencontent\Opendata\Api\AttributeConverter;
use eZContentObjectAttribute;


class Matrix extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        /** @var \eZMatrix $attributeContents */
        $attributeContents = $attribute->content();
        $cellList = (array) $attributeContents->attribute( 'cells' );
        $availableCells = array();
        for ( $i = 0; $i < count( $cellList ); $i++ )
        {
            $availableCells[] = array( $cellList[$i] => $cellList[++$i] );
        }
        $content['content'] = $availableCells;
        return $content;
    }

    public function type()
    {
        return array( 'identifier' => 'array' );
    }
}