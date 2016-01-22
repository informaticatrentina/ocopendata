<?php

namespace Opencontent\Opendata\Api;

use Opencontent\Opendata\Api\Exception\BaseException;
use Opencontent\Opendata\Api\QueryLanguage\EzFind\QueryBuilder as EzFindQueryBuilder;
use Opencontent\Opendata\Api\Values\SearchResults;
use Exception;
use eZINI;

class ContentSearch
{
    protected $query;

    /**
     * @var EnvironmentSettings
     */
    protected $currentEnvironmentSettings;

    public function setEnvironment( EnvironmentSettings $environmentSettings )
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    public function search( $query )
    {
        $builder = new EzFindQueryBuilder();
        $queryObject = $builder->instanceQuery( $query );
        $ezFindQuery = $queryObject->convert();

        $searchResults = new SearchResults();
        if ( $this->currentEnvironmentSettings->debug )
        {
            $searchResults->query = array(
                'string' => (string)$queryObject,
                'eZFindQuery' => $ezFindQuery
            );
        }
        else
        {
            $searchResults->query = (string)$queryObject;
        }
        return $searchResults;
    }
}