<?php

namespace Opencontent\Opendata\Api\Values;


use Opencontent\Opendata\GeoJson\Feature;
use Opencontent\Opendata\GeoJson\FeatureCollection;

class SearchResults
{
    /**
     * @var string
     */
    public $query;

    /**
     * @var string
     */
    public $nextPageQuery;

    /**
     * @var int
     */
    public $totalCount;

    /**
     * @var Content[]|Feature[]
     */
    public $searchHits = array();


}