<?php

use Opencontent\Opendata\Api\EnvironmentSettings;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\Values\ContentData;
use Opencontent\Opendata\GeoJson\FeatureCollection;
use Opencontent\Opendata\GeoJson\Feature;

class GeoEnvironmentSettings extends EnvironmentSettings
{

    protected $maxSearchLimit = 500;

    protected $defaultSearchLimit = 500;

    public function filterContent( Content $content )
    {
        $language = isset( $this->request->get['language'] ) ? $this->request->get['language'] : null;
        return $content->geoJsonSerialize( $language );
    }

    protected function filterAttributes( Content $content )
    {
        $flatData = array();
        foreach( $content->data as $language => $data )
        {
            foreach( $data as $identifier => $attribute )
            {
                $flatData[$language][$identifier] = $attribute;
            }
        }
        $content->data = new ContentData( $flatData );
        return $content;
    }

    public function filterSearchResult( \Opencontent\Opendata\Api\Values\SearchResults $searchResults )
    {
        return $searchResults->geoJsonSerialize();
    }

    public function filterQuery( \ArrayObject $query )
    {
        $classRepository = new \Opencontent\Opendata\Api\ClassRepository();
        $attributes = $classRepository->listAttributesGroupedByDatatype();
        if ( isset( $attributes['ezgmaplocation'] ) )
        {
            $filters = array();
            foreach ( $attributes['ezgmaplocation'] as $identifier => $classes )
            {
//                $filters[] = array(
//                    "subattr_{$identifier}___longitude____f:[* TO *]",
//                    "!subattr_{$identifier}___longitude____f:0"
//                );
                $filters[] = "subattr_{$identifier}___longitude____f:[* TO *]";
            }
            if ( !isset( $query['Filter'] ) )
            {
                $query['Filter'] = array();
            }
            if ( count( $filters ) > 1 )
                array_unshift( $filters, 'or' );
            $query['Filter'][] = $filters;
        }
        else
        {
            throw new RuntimeException( "No attribute type ezgmaplocation found" );
        }
        return parent::filterQuery( $query );
    }
}