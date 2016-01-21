<?php

namespace Opencontent\Opendata\Api;

use eZContentObject;
use Opencontent\Opendata\Api\Gateway\FileSystem;
use Opencontent\Opendata\Api\Gateway\Database;
use Opencontent\Opendata\Api\Gateway\SolrStorage;
use Opencontent\Opendata\Api\Exception\ForbiddenException;

class ContentRepository
{
    /**
     * @var EnvironmentSettings
     */
    protected $currentEnvironmentSettings;

    public function __construct()
    {
//        $this->gateway = new Database();      // fallback per tutti
//        $this->gateway = new SolrStorage();   // usa solr storage per restituire oggetti (sembra lento...)
        $this->gateway = new FileSystem();      // scrive cache sul filesystem (cluster safe)
    }

    public function setEnvironment( EnvironmentSettings $environmentSettings )
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    public function read( $contentObjectIdentifier )
    {
        $content = $this->gateway->loadContent( $contentObjectIdentifier );
        if ( !$content->canRead() )
            throw new ForbiddenException( $contentObjectIdentifier, 'read' );

        return $this->currentEnvironmentSettings->filterContent( $content );
    }

    public function create( $data )
    {
        return 'todo';
    }

    public function update( $data )
    {
        return 'todo';
    }

    public function delete( $data )
    {
        return 'todo';
    }
}