<?php

namespace Opencontent\Opendata\Api\Gateway;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\Values\ContentData;
use Opencontent\Opendata\Api\Values\Metadata;
use eZContentObject;
use eZContentObjectAttribute;
use eZSection;
use eZContentLanguage;


class Database
{
    public function loadContent( eZContentObject $contentObject )
    {
        $languages = eZContentLanguage::fetchLocaleList();
        $availableLanguages = $contentObject->attribute( 'available_languages' );
        $content =  new Content();
        $metadata = new Metadata();
        $metadata->id = $contentObject->attribute( 'id' );
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
                    $converter = \Opencontent\Opendata\Api\AttributeConverterLoader::load(
                        $contentObject->attribute( 'class_identifier' ),
                        $identifier,
                        $attribute
                    );
                    if ( $converter->isPublic() )
                    {
                        $localeAttributes[$converter->getIdentifier()] = $converter->getValue();
                    }
                }
                $attributes[$language] = $localeAttributes;
            }
        }
        $content->data = new ContentData( $attributes );
        return $content;
    }
}