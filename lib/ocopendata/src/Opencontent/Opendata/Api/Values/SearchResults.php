<?php

namespace Opencontent\Opendata\Api\Values;


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
     * @var Content[]
     */
    public $searchHits = array();


}