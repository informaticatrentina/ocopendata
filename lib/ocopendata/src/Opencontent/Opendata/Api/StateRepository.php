<?php

namespace Opencontent\Opendata\Api;

use eZDir;
use eZSys;
use eZClusterFileHandler;
use eZContentObjectStateGroup;
use Opencontent\Opendata\Api\Exception\NotFoundException;

class StateRepository
{
    public function load( $identifier )
    {
        $all = $this->internalLoadStates();
        foreach( $all as $state )
        {
            if ( ( is_numeric( $identifier ) && $state['id'] == $identifier )
                 || ( $state['identifier'] == $identifier ) )
            {
                return $state;
            }
        }
        throw new NotFoundException( $identifier, 'State' );
    }

    public function loadAll()
    {
        return $this->internalLoadStates();
    }

    protected function internalLoadStates()
    {
        return $this->getCacheManager()->processCache(
            array( __CLASS__, 'retrieveCache' ),
            array( __CLASS__, 'generateCache' ),
            null,
            null,
            'states'
        );
    }

    protected static function getCacheManager()
    {
        $cacheFile = 'states.cache';
        $cacheFilePath = eZDir::path(
            array( eZSys::cacheDirectory(), 'ocopendata', $cacheFile )
        );

        return eZClusterFileHandler::instance( $cacheFilePath );
    }

    public function clearCache()
    {
        $this->getCacheManager()->purge();
    }

    public static function retrieveCache( $file, $mtime, $identifier )
    {
        $content = include( $file );

        return $content;
    }

    public static function generateCache( $file, $identifier )
    {
        $stateList = array();

        /** @var eZContentObjectStateGroup[] $groups */
        $groups = eZContentObjectStateGroup::fetchObjectList( eZContentObjectStateGroup::definition() ); //@todo

        foreach( $groups as $group )
        {
            $stateGroup = array(
                'group_id' => $group->attribute( 'id' ),
                'group_identifier' => $group->attribute( 'identifier' )
            );

            /** @var \eZContentObjectState $state */
            foreach( $group->attribute( 'states' ) as $state )
            {
                $stateList[] = array_merge(
                    array(
                    'id' => $state->attribute( 'id' ),
                    'identifier' => $stateGroup['group_identifier'] . '.' . $state->attribute( 'identifier' ),
                    'state_identifier' => $state->attribute( 'identifier' ),
                    ), $stateGroup
                );
            }
        }

        return array(
            'content' => $stateList,
            'scope' => 'ocopendata-cache',
            'datatype' => 'php',
            'store' => true
        );
    }
}