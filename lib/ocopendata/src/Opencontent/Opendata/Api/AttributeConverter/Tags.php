<?php

namespace Opencontent\Opendata\Api\AttributeConverter;


use eZContentClassAttribute;
use eZContentObjectAttribute;
use Opencontent\Opendata\Api\Exception\InvalidInputException;
use eZTagsFunctionCollection;
use Opencontent\Opendata\Api\PublicationProcess;

class Tags extends Base
{

    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        $content['content'] = $attribute->metaData();
        return $content;
    }

    public function set( $data, PublicationProcess $process )
    {
        $tagIDs = '';
        $tagKeywords = '';
        $tagParents = '';

        foreach ($data as $keyword) {

            $keywordsFound = eZTagsFunctionCollection::fetchTagsByKeyword($keyword);
            if ( !empty( $keywordsFound ) )
            {
                $tagIDs .= $keywordsFound['result'][0]->ID.'|#';
                $tagKeywords .= $keywordsFound['result'][0]->Keyword.'|#';
                $tagParents .= $keywordsFound['result'][0]->ParentID.'|#';
            }
        }

        $tagIDs = implode( '|#', $tagIDs );
        $tagKeywords = implode( '|#', $tagKeywords );
        $tagParents = implode( '|#', $tagParents );

        $data = $tagIDs . '|#' . $tagKeywords . '|#' . $tagParents;
        return parent::set( $data, $process );
    }

    public static function validate( $identifier, $data, eZContentClassAttribute $attribute )
    {
        if ( !is_array( $data ) )
        {
            throw new InvalidInputException( 'Invalid data', $identifier, $data );
        }
    }

    public function toCSVString($content, $params = null)
    {
        return implode(',', $content);
    }
}