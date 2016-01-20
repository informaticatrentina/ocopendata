<?php

namespace OpenContent\Opendata\Api;


class ContentBrowser
{
    /**
     * @var EnvironmentSettings
     */
    protected $currentEnvironmentSettings;

    public function setEnvironment( EnvironmentSettings $environmentSettings )
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    public function browse( $nodeId )
    {
        return 'todo';
    }
}