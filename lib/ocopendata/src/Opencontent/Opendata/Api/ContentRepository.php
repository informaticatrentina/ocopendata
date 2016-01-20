<?php

namespace OpenContent\Opendata\Api;

use OpenContent\Opendata\Api\Values\Content;
use OpenContent\Opendata\Api\Values\Metadata;
use eZContentObject;
use eZContentObjectAttribute;
use eZSection;
use eZLocale;

class ContentRepository
{
    protected $currentLanguage;

    protected $currentEnvironmentSettings;

    public function __construct()
    {
        $this->currentLanguage = eZLocale::currentLocaleCode();
    }

    public function setEnvironment( EnvironmentSettings $environmentSettings )
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    public function setLanguage( $currentLanguage )
    {
        $this->currentLanguage = $currentLanguage;
    }

    public function read( $contentObjectIdentifier )
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
        $this->checkAccess( $contentObject );

        return $this->loadContent( $contentObject );
    }

    protected function checkAccess( eZContentObject $contentObject )
    {
        if ( !$contentObject->attribute( 'can_read' ) )
        {
            throw new Exception\ForbiddenException( $contentObject->attribute( 'id' ), 'read' );
        }
    }

    protected function loadContent( eZContentObject $contentObject )
    {
        $content =  new Content();
        $metadata = new Metadata();
        $metadata->id = $contentObject->attribute( 'id' );
        $metadata->name = $contentObject->name( false, $this->getCurrentLanguage() );
        $metadata->remoteId = $contentObject->attribute( 'remote_id' );
        $metadata->ownerId = $contentObject->attribute( 'owner_id' );
        $metadata->classIdentifier = $contentObject->attribute( 'class_identifier' );
        $metadata->nodeIds = array();
        foreach( $contentObject->assignedNodes( false ) as $node )
            $metadata->nodeIds[] = $node['node_id'];
        $metadata->parentNodeIds = $contentObject->parentNodeIDArray();
        $metadata->published = $contentObject->attribute( 'published' );
        $metadata->modified = $contentObject->attribute( 'modified' );
        $section = eZSection::fetch( $contentObject->attribute( 'section_id' ) );
        if ( $section instanceof eZSection )
            $metadata->sectionIdentifier = $section->attribute( 'identifier' );
        $metadata->statusIdentifiers = $contentObject->stateIdentifierArray();
        $metadata->language = $contentObject->attribute( 'current_language' );
        $content->metadata = $metadata;

        $content->data = array();
        /** @var eZContentObjectAttribute[] $dataMap */
        $dataMap = $contentObject->fetchDataMap( false, $this->getCurrentLanguage() );
        foreach( $dataMap as $identifier => $attribute )
        {
            $converter = \Opencontent\Opendata\Api\AttributeConverterLoader::load(
                $contentObject->attribute( 'class_identifier' ),
                $identifier,
                $attribute,
                $this->currentEnvironmentSettings
            );
            if ( $converter->isPublic() )
            {
                $content->data[$converter->getIdentifier()] = $converter->getValue();
            }
        }

        return $content;
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

    public function getCurrentLanguage()
    {
        return $this->currentLanguage;
    }
}