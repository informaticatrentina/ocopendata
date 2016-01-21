<?php

namespace Opencontent\Opendata\Api\Gateway;
use Opencontent\Opendata\Api\Gateway;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\Values\ContentData;
use Opencontent\Opendata\Api\Values\Metadata;
use Opencontent\Opendata\Api\AttributeConverterLoader;
use eZContentObject;
use eZContentObjectAttribute;
use eZSection;
use eZContentLanguage;
use Opencontent\Opendata\Api\Exception\NotFoundException;
use Opencontent\Opendata\Api\Exception\ForbiddenException;


class Database implements Gateway
{
    /**
     * @param $contentObjectIdentifier
     *
     * @return Content
     * @throws NotFoundException
     */
    public function loadContent( $contentObjectIdentifier )
    {
        $contentObject = $this->findContent( $contentObjectIdentifier );
        $languages = eZContentLanguage::fetchLocaleList();
        $availableLanguages = $contentObject->attribute( 'available_languages' );
        $content =  new Content();
        $metadata = new Metadata();
        $metadata->id = (int) $contentObject->attribute( 'id' );
        $names = array();
        foreach( $languages as $language )
        {
            if ( in_array( $language, $availableLanguages ) )
            {
                $names[$language] = $contentObject->name( false, $language );
            }
        }
        $metadata->name = $names;
        $metadata->remoteId = $contentObject->attribute( 'remote_id' );
        $metadata->ownerId = (int) $contentObject->attribute( 'owner_id' );
        $metadata->classIdentifier = $contentObject->attribute( 'class_identifier' );
        $metadata->nodeIds = array();
        foreach( $contentObject->assignedNodes( false ) as $node )
            $metadata->nodeIds[] = (int) $node['node_id'];
        $metadata->parentNodeIds = array_map( 'intval', $contentObject->parentNodeIDArray() );
        $metadata->published = (int) $contentObject->attribute( 'published' );
        $metadata->modified = (int) $contentObject->attribute( 'modified' );
        $section = eZSection::fetch( $contentObject->attribute( 'section_id' ) );
        if ( $section instanceof eZSection )
            $metadata->sectionIdentifier = $section->attribute( 'identifier' );
        $metadata->statusIdentifiers = $contentObject->stateIdentifierArray();
        $metadata->languages = $availableLanguages;
        $content->metadata = $metadata;

        $attributes = array();
        foreach( $languages as $language )
        {
            if ( in_array( $language, $availableLanguages ) )
            {
                $localeAttributes = array();
                /** @var eZContentObjectAttribute[] $dataMap */
                $dataMap = $contentObject->fetchDataMap( false, $language );
                foreach ( $dataMap as $identifier => $attribute )
                {
                    $converter = AttributeConverterLoader::load(
                        $contentObject->attribute( 'class_identifier' ),
                        $identifier,
                        $attribute->attribute( 'data_type_string' )
                    );
                    $localeAttributes[$converter->getIdentifier()] = $converter->get( $attribute );
                }
                $attributes[$language] = $localeAttributes;
            }
        }
        $content->data = new ContentData( $attributes );
        return $content;
    }

    /**
     * @param $contentObjectIdentifier
     *
     * @return eZContentObject|null
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    protected function findContent( $contentObjectIdentifier )
    {
        $contentObject = eZContentObject::fetch( intval( $contentObjectIdentifier ) );
        if ( !$contentObject instanceof eZContentObject )
        {
            $contentObject = eZContentObject::fetchByRemoteID( $contentObjectIdentifier );
        }
        if ( !$contentObject instanceof eZContentObject )
        {
            throw new NotFoundException( $contentObjectIdentifier );
        }
        return $contentObject;
    }

    public function checkAccess( $contentObjectIdentifier )
    {
        $contentObject = $this->findContent( $contentObjectIdentifier );
        if ( !$contentObject->attribute( 'can_read' ) )
        {
            throw new ForbiddenException( $contentObject->attribute( 'id' ), 'read' );
        }
    }
}