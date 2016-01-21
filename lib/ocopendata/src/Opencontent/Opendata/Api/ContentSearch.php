<?php

namespace Opencontent\Opendata\Api;

use Opencontent\Opendata\Api\Values\SearchResults;

class ContentSearch
{
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
        return new SearchResults();
    }
}