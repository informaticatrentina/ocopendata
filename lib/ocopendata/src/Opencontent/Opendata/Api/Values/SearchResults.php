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
    public $count;

    /**
     * @var Content[]
     */
    public $contents;

    /**
     * @var string
     */
    public $nextPageQuery;

}