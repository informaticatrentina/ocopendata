<?php

namespace OpenContent\Opendata\Cache;

use Opencontent\Opendata\Values\ContentObject;
use eZContentObject;
use eZContentLanguage;
use eZDir;
use eZSys;
use eZClusterFileHandler;

class ContentObjectService
{

    public function loadContentObject( $contentObjectId )
    {
        return $this->getCacheManager( $contentObjectId )->processCache(
            array( 'OpenContent\Opendata\Cache\ContentObjectService', 'retrieveCache' ),
            array( 'OpenContent\Opendata\Cache\ContentObjectService', 'generateCache' ),
            null,
            null,
            $contentObjectId
        );
    }

    public function loadContentObjectByRemoteId( $remoteId )
    {
        //@todo
    }

    public function createContentObject( $contentObjectCreateStruct )
    {
        //@todo
    }

    public function updateContentObject()
    {
        //@todo
    }

    public function deleteContentObject( eZContentObject $contentObject )
    {
        //@todo
    }

    public function getCurrentLanguage()
    {
        //@todo
        return 'todo';
    }

    public function getCacheManager( $contentObjectId )
    {
        $cacheFile = $contentObjectId . '.cache';
        $language = $this->getCurrentLanguage();
        $extraPath = eZDir::filenamePath( $contentObjectId );
        $cacheFilePath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata', $language, 'object', $extraPath, $cacheFile ) );
        return eZClusterFileHandler::instance( $cacheFilePath );
    }

    public static function clearCache( $contentObjectId )
    {
        $languages = eZContentLanguage::fetchLocaleList();
        if ( !empty( $languages ) )
        {
            $commonPath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata' ) );
            $fileHandler = eZClusterFileHandler::instance();
            $commonSuffix = "object/" . eZDir::filenamePath( $contentObjectId );
            $fileHandler->fileDeleteByDirList( $languages, $commonPath, $commonSuffix );
        }
    }

    public static function retrieveCache( $file, $mtime, $contentObjectId )
    {
        $object = include( $file );
        return $object;
    }

    public static function generateCache( $file, $contentObjectId )
    {
        $object = self::internalLoadContentObjectCacheable( $contentObjectId );
        return array( 'content'  => $object,
                      'scope'    => 'ocopendata-cache',
                      'datatype' => 'php',
                      'store'    => true );

    }

    protected static function internalLoadContentObjectCacheable( $contentObjectId )
    {
        //@todo
        return new ContentObject();
    }

}