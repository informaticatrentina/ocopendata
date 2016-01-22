<?php

namespace Opencontent\Opendata\Api\Values;

use eZContentObject;
use eZContentObjectAttribute;
use eZSection;
use eZContentLanguage;
use Opencontent\Opendata\Api\Exception\OutOfRangeException;
use Opencontent\Opendata\Api\AttributeConverterLoader;
use eZUser;
use eZPreferences;
use eZContentObjectTreeNode;

class Content
{
    /**
     * @var Metadata
     */
    public $metadata;

    /**
     * @var ContentData
     */
    public $data;

    public function __construct( array $properties = array() )
    {
        foreach ( $properties as $property => $value )
        {
            if ( property_exists( $this, $property ) )
            {
                $this->$property = $value;
            }
            else
            {
                throw new OutOfRangeException( $property );
            }
        }
    }

    public static function __set_state( array $array )
    {
        return new static( $array );
    }

    public function jsonSerialize()
    {
        return array(
            'metadata' => $this->metadata->jsonSerialize(),
            'data' => $this->data->jsonSerialize()
        );
    }

    /**
     * @param eZContentObject $contentObject
     *
     * @return Content
     */
    public static function createFromEzContentObject( eZContentObject $contentObject )
    {
        $languages = eZContentLanguage::fetchLocaleList();
        /** @var eZContentLanguage[] $availableLanguages */
        $availableLanguages = array_keys( $contentObject->allLanguages() );
        $content = new Content();
        $metadata = new Metadata();
        $metadata->id = (int)$contentObject->attribute( 'id' );
        $names = array();
        foreach ( $languages as $language )
        {
            if ( in_array( $language, $availableLanguages ) )
            {
                $names[$language] = $contentObject->name( false, $language );
            }
        }
        $metadata->name = $names;
        $metadata->remoteId = $contentObject->attribute( 'remote_id' );
        $metadata->ownerId = (int)$contentObject->attribute( 'owner_id' );
        $metadata->classIdentifier = $contentObject->attribute( 'class_identifier' );
        $metadata->classId = $contentObject->attribute( 'contentclass_id' );
        $metadata->mainNodeId = $contentObject->attribute( 'main_node_id' );
        $metadata->parentNodes = array();
        foreach( $contentObject->attribute( 'parent_nodes' ) as $node )
        {
            $parentNode = eZContentObjectTreeNode::fetch( $node, false, false );
            $metadata->parentNodes[] = array(
                'id' => (int)$parentNode['node_id'],
                'depth' => (int)$parentNode['depth'],
                'path_string' => $parentNode['path_string']
            );
        }
        $metadata->assignedNodes = array();
        /** @var \eZContentObjectTreeNode $node */
        foreach( $contentObject->attribute( 'assigned_nodes' ) as $node )
        {
            $metadata->assignedNodes[] = array(
                'id' => (int)$node->attribute( 'node_id' ),
                'depth' => (int)$node->attribute( 'depth' ),
                'path_string' => $node->attribute( 'path_string' )
            );
        }
        $metadata->published = (int)$contentObject->attribute( 'published' );
        $metadata->modified = (int)$contentObject->attribute( 'modified' );
        $section = eZSection::fetch( $contentObject->attribute( 'section_id' ) );
        if ( $section instanceof eZSection )
        {
            $metadata->sectionIdentifier = $section->attribute( 'identifier' );
            $metadata->sectionId = $section->attribute( 'id' );
        }
        $metadata->stateIdentifiers = $contentObject->stateIdentifierArray();
        $metadata->stateIds = $contentObject->stateIDArray();
        $metadata->languages = $availableLanguages;
        $content->metadata = $metadata;

        $attributes = array();
        foreach ( $languages as $language )
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

    public function canRead( eZUser $user = null )
    {
        if ( $user == null )
            $user = eZUser::currentUser();
        $userID = $user->attribute( 'contentobject_id' );
        $accessResult = $user->hasAccessTo( 'content', 'read' );
        $accessWord = $accessResult['accessWord'];

        if ( $accessWord == 'yes' )
        {
            return true;
        }

        if ( $accessWord == 'no' )
        {
            return false;
        }

        $access = 'denied';
        $policies =& $accessResult['policies'];
        foreach ( array_keys( $policies ) as $pkey )
        {
            $limitationArray =& $policies[$pkey];
            if ( $access == 'allowed' )
            {
                break;
            }

            $limitationList = array();
            if ( isset( $limitationArray['Subtree'] ) )
            {
                $checkedSubtree = false;
            }
            else
            {
                $checkedSubtree = true;
                $accessSubtree = false;
            }
            if ( isset( $limitationArray['Node'] ) )
            {
                $checkedNode = false;
            }
            else
            {
                $checkedNode = true;
                $accessNode = false;
            }
            foreach ( array_keys( $limitationArray ) as $key )
            {
                $access = 'denied';
                switch ( $key )
                {
                    case 'Class':
                    {
                        if ( in_array( $this->metadata->classId, $limitationArray[$key] ) )
                        {
                            $access = 'allowed';
                        }
                        else
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                    }
                        break;

                    case 'ParentClass':
                    {

                        if ( in_array( $this->metadata->classId, $limitationArray[$key] ) )
                        {
                            $access = 'allowed';
                        }
                        else
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                    }
                        break;

                    case 'ParentDepth':
                    {
                        $assignedNodes = $this->metadata->assignedNodes;
                        if ( count( $assignedNodes ) > 0 )
                        {
                            foreach ( $assignedNodes as $assignedNode )
                            {
                                $depth = $assignedNode['depth'];
                                if ( in_array( $depth, $limitationArray[$key] ) )
                                {
                                    $access = 'allowed';
                                    break;
                                }
                            }
                        }

                        if ( $access != 'allowed' )
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                    }
                        break;

                    case 'Section':
                    case 'User_Section':
                    {
                        if ( in_array( $this->metadata->sectionId, $limitationArray[$key] ) )
                        {
                            $access = 'allowed';
                        }
                        else
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                    }
                        break;

                    case 'Language':
                    {
                        //@todo
                    }
                        break;

                    case 'Owner':
                    case 'ParentOwner':
                    {
                        // if limitation value == 2, anonymous limited to current session.
                        if ( in_array( 2, $limitationArray[$key] ) && $user->isAnonymous() )
                        {
                            $createdObjectIDList = eZPreferences::value( 'ObjectCreationIDList' );
                            if ( $createdObjectIDList
                                 && in_array( $this->metadata->id, unserialize( $createdObjectIDList ) )
                            )
                            {
                                $access = 'allowed';
                            }
                        }
                        else if ( $this->metadata->ownerId == $userID || $this->metadata->id == $userID )
                        {
                            $access = 'allowed';
                        }
                        if ( $access != 'allowed' )
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                    }
                        break;

                    case 'Group':
                    case 'ParentGroup':
                    {
                        $access = $this->checkGroupLimitationAccess(
                            $limitationArray[$key],
                            $userID
                        );

                        if ( $access != 'allowed' )
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                    }
                        break;

                    case 'State':
                    {
                        if ( count( array_intersect( $limitationArray[$key], $this->metadata->stateIds ) ) == 0
                        )
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                        else
                        {
                            $access = 'allowed';
                        }
                    }
                        break;

                    case 'Node':
                    {
                        $accessNode = false;
                        $mainNodeID = $this->metadata->mainNodeId;
                        foreach ( $limitationArray[$key] as $nodeID )
                        {
                            $node = eZContentObjectTreeNode::fetch( $nodeID, false, false ); //@todo
                            $limitationNodeID = $node['main_node_id'];
                            if ( $mainNodeID == $limitationNodeID )
                            {
                                $access = 'allowed';
                                $accessNode = true;
                                break;
                            }
                        }
                        if ( $access != 'allowed' && $checkedSubtree && ( !isset( $accessSubtree ) || !$accessSubtree ) )
                        {
                            $access = 'denied';
                            // ??? TODO: if there is a limitation on Subtree, return two limitations?
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                        else
                        {
                            $access = 'allowed';
                        }
                        $checkedNode = true;
                    }
                        break;

                    case 'Subtree':
                    {
                        $accessSubtree = false;
                        $assignedNodes = $this->metadata->assignedNodes;
                        if ( count( $assignedNodes ) != 0 )
                        {
                            foreach ( $assignedNodes as $assignedNode )
                            {
                                $path = $assignedNode['path_string'];
                                $subtreeArray = $limitationArray[$key];
                                foreach ( $subtreeArray as $subtreeString )
                                {
                                    if ( strstr( $path, $subtreeString ) )
                                    {
                                        $access = 'allowed';
                                        $accessSubtree = true;
                                        break;
                                    }
                                }
                            }
                        }
                        else
                        {
                            $parentNodes = $this->metadata->parentNodes;
                            if ( count( $parentNodes ) == 0 )
                            {
                                if ( $this->metadata->id == $userID || $this->metadata->id == $userID )
                                {
                                    $access = 'allowed';
                                    $accessSubtree = true;
                                }
                            }
                            else
                            {
                                foreach ( $parentNodes as $parentNode )
                                {
                                    $path = $parentNode['path_string'];

                                    $subtreeArray = $limitationArray[$key];
                                    foreach ( $subtreeArray as $subtreeString )
                                    {
                                        if ( strstr( $path, $subtreeString ) )
                                        {
                                            $access = 'allowed';
                                            $accessSubtree = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        if ( $access != 'allowed' && $checkedNode && ( !isset($accessNode) || !$accessNode ) )
                        {
                            $access = 'denied';
                            // ??? TODO: if there is a limitation on Node, return two limitations?
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                        else
                        {
                            $access = 'allowed';
                        }
                        $checkedSubtree = true;
                    }
                        break;

                    case 'User_Subtree':
                    {
                        $assignedNodes = $this->metadata->assignedNodes;
                        if ( count( $assignedNodes ) != 0 )
                        {
                            foreach ( $assignedNodes as $assignedNode )
                            {
                                $path = $assignedNode['path_string'];
                                $subtreeArray = $limitationArray[$key];
                                foreach ( $subtreeArray as $subtreeString )
                                {
                                    if ( strstr( $path, $subtreeString ) )
                                    {
                                        $access = 'allowed';
                                    }
                                }
                            }
                        }
                        else
                        {
                            $parentNodes = $this->metadata->parentNodes;
                            if ( count( $parentNodes ) == 0 )
                            {
                                if ( $this->metadata->id == $userID || $this->metadata->id == $userID )
                                {
                                    $access = 'allowed';
                                }
                            }
                            else
                            {
                                foreach ( $parentNodes as $parentNode )
                                {
                                    $path = $parentNode['path_string'];

                                    $subtreeArray = $limitationArray[$key];
                                    foreach ( $subtreeArray as $subtreeString )
                                    {
                                        if ( strstr( $path, $subtreeString ) )
                                        {
                                            $access = 'allowed';
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        if ( $access != 'allowed' )
                        {
                            $access = 'denied';
                            $limitationList = array(
                                'Limitation' => $key,
                                'Required' => $limitationArray[$key]
                            );
                        }
                    }
                        break;

                    default:
                    {
                        if ( strncmp( $key, 'StateGroup_', 11 ) === 0 )
                        {
                            if ( count( array_intersect( $limitationArray[$key], $this->metadata->stateIds ) ) == 0 )
                            {
                                $access = 'denied';
                                $limitationList = array(
                                    'Limitation' => $key,
                                    'Required' => $limitationArray[$key]
                                );
                            }
                            else
                            {
                                $access = 'allowed';
                            }
                        }
                    }
                }
                if ( $access == 'denied' )
                {
                    break;
                }
            }

            $policyList[] = array(
                'PolicyID' => $pkey,
                'LimitationList' => $limitationList
            );
        }

        return !( $access == 'denied' );
    }

    protected function checkGroupLimitationAccess( $limitationValueList, $userID )
    {
        $access = 'denied';

        if ( is_array( $limitationValueList ) && is_numeric( $userID ) )
        {
            // limitation value == 1, means "self group"
            if ( in_array( 1, $limitationValueList ) )
            {
                // no need to check groups if user ownes this object
                $ownerID = $this->metadata->ownerId;
                if ( $ownerID == $userID || $this->metadata->id == $userID )
                {
                    $access = 'allowed';
                }
                else
                {
                    // get parent node ids for 'user' and 'owner'
                    $groupList = eZContentObjectTreeNode::getParentNodeIdListByContentObjectID( array( $userID, $ownerID ), true );

                    // find group(s) which is common for 'user' and 'owner'
                    $commonGroup = array_intersect( $groupList[$userID], $groupList[$ownerID] );

                    if ( count( $commonGroup ) > 0 )
                    {
                        // ok, we have at least 1 common group
                        $access = 'allowed';
                    }
                }
            }
        }

        return $access;
    }

    public function __toString()
    {
        return (string) $this->metadata->id;
    }
}