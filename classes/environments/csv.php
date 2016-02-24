<?php

use Opencontent\QueryLanguage\QueryBuilder;
use Opencontent\Opendata\Api\Values\SearchResults;

class CsvEnvironmentSettings extends DefaultEnvironmentSettings
{
    public function filterSearchResult(SearchResults $searchResults, \ArrayObject $query, QueryBuilder $builder)
    {
        return $searchResults;
    }
}