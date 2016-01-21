<?php

namespace Opencontent\Opendata\Api;


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

    public function search( $query, $page )
    {
        return 'todo';
    }
}