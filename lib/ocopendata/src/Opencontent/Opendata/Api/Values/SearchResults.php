<?php

namespace Opencontent\Opendata\Api\Values;


class SearchResults
{
    /**
     * @var string
     */
    public $query;

    /**
     * @var array
     */
    public $ezFindQuery;

    /**
     * @var int
     */
    public $totalCount;

    /**
     * @var Content[]
     */
    public $searchHits = array();

    /**
     * @var string
     */
    public $nextPageQuery;

}