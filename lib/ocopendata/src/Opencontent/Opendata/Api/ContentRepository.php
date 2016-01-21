<?php

namespace Opencontent\Opendata\Api;

use eZContentObject;
use Opencontent\Opendata\Api\Gateway\FileSystem;
use Opencontent\Opendata\Api\Gateway\Database;
use Opencontent\Opendata\Api\Exception\NotFoundException;
use Opencontent\Opendata\Api\Exception\ForbiddenException;

class ContentRepository
{
    protected $currentEnvironmentSettings;

    public function __construct()
    {
        $this->gateway = new Database();
//        $this->gateway = new FileSystem();
    }

    public function setEnvironment( EnvironmentSettings $environmentSettings )
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    public function read( $contentObjectIdentifier )
    {
        $contentObject = $this->findContent( $contentObjectIdentifier );
        $this->checkAccess( $contentObject );
        return $this->gateway->loadContent( $contentObject );
    }

    protected function findContent( $contentObjectIdentifier )
    {
        $contentObject = eZContentObject::fetch( intval( $contentObjectIdentifier ) );
        if ( !$contentObject instanceof eZContentObject )
        {
            $contentObject = eZContentObject::fetchByRemoteID( $contentObjectIdentifier );
        }
        if ( !$contentObject instanceof eZContentObject )
        {
            throw new Exception\NotFoundException( $contentObjectIdentifier );
        }
        return $contentObject;
    }

    protected function checkAccess( eZContentObject $contentObject )
    {
        if ( !$contentObject->attribute( 'can_read' ) )
        {
            throw new Exception\ForbiddenException( $contentObject->attribute( 'id' ), 'read' );
        }
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