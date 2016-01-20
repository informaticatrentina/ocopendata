<?php

namespace OpenContent\Opendata\Api\Gateway;

use Opencontent\Opendata\Api\Gateway\Database;
use eZContentObject;
use eZDir;
use eZSys;
use eZClusterFileHandler;

class FileSystem
{

    public function loadContent( eZContentObject $contentObject )
    {
        $contentObjectId = $contentObject->attribute( 'id' );
        return $this->getCacheManager( $contentObjectId )->processCache(
            array( __CLASS__, 'retrieveCache' ),
            array( __CLASS__, 'generateCache' ),
            null,
            null,
            $contentObjectId
        );
    }

    protected static function getCacheManager( $contentObjectId )
    {
        $cacheFile = $contentObjectId . '.cache';
        $extraPath = eZDir::filenamePath( $contentObjectId );
        $cacheFilePath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata', 'content', $extraPath, $cacheFile ) );
        return eZClusterFileHandler::instance( $cacheFilePath );
    }

    public function clearCache( $contentObjectId )
    {
        $this->getCacheManager( $contentObjectId )->purge();
    }

    public static function retrieveCache( $file, $mtime, $contentObjectId )
    {
        $content = include( $file );
        return $content;
    }

    public static function generateCache( $file, $contentObjectId )
    {
        $contentObject = eZContentObject::fetch( $contentObjectId );
        $gateway = new Database();
        $content = $gateway->loadContent( $contentObject );
        return array( 'content'  => $content,
                      'scope'    => 'ocopendata-cache',
                      'datatype' => 'php',
                      'store'    => true );

    }
}