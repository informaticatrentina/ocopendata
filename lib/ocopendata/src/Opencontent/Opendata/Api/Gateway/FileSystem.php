<?php

namespace Opencontent\Opendata\Api\Gateway;

use Opencontent\Opendata\Api\Gateway;
use Opencontent\Opendata\Api\Values\Content;
use eZDir;
use eZSys;
use eZClusterFileHandler;

class FileSystem extends Database
{

    public function loadContent( $contentObjectIdentifier )
    {
        return $this->getCacheManager( $contentObjectIdentifier, 'content' )->processCache(
            array( __CLASS__, 'retrieveCache' ),
            array( __CLASS__, 'generateCache' ),
            null,
            null,
            $contentObjectIdentifier
        );
    }

    protected static function getCacheManager( $contentObjectIdentifier, $type )
    {
        $cacheFile = $contentObjectIdentifier . '.cache';
        $extraPath = eZDir::filenamePath( $contentObjectIdentifier );
        $cacheFilePath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata', $type, $extraPath, $cacheFile ) );
        return eZClusterFileHandler::instance( $cacheFilePath );
    }

    public function clearCache( $contentObjectIdentifier, $type = 'content' )
    {
        $this->getCacheManager( $contentObjectIdentifier, $type )->purge();
    }

    public static function retrieveCache( $file, $mtime, $contentObjectIdentifier )
    {
        $content = include( $file );
        return $content;
    }

    public static function generateCache( $file, $contentObjectIdentifier )
    {
        $contentObject = self::findContent( $contentObjectIdentifier );
        $content = Content::createFromEzContentObject( $contentObject );;
        return array( 'content'  => $content,
                      'scope'    => 'ocopendata-cache',
                      'datatype' => 'php',
                      'store'    => true );

    }
}