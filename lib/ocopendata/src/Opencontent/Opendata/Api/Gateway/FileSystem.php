<?php

namespace Opencontent\Opendata\Api\Gateway;

use Opencontent\Opendata\Api\Gateway;
use Opencontent\Opendata\Api\Values\Content;
use eZDir;
use eZSys;
use eZClusterFileHandler;
use eZDB;
use Opencontent\Opendata\Api\Exception\NotFoundException;

class FileSystem extends Database
{

    public function loadContent( $contentObjectIdentifier )
    {
        $contentObjectId = self::findContent( $contentObjectIdentifier );
        return $this->getCacheManager( $contentObjectId, 'content' )->processCache(
            array( __CLASS__, 'retrieveCache' ),
            array( __CLASS__, 'generateCache' ),
            null,
            null,
            $contentObjectId
        );
    }

    /**
     * @param $contentObjectIdentifier
     *
     * @return int
     * @throws NotFoundException
     */
    protected static function findContent( $contentObjectIdentifier )
    {
        $contentObjectIdentifierAsInt = (int) $contentObjectIdentifier;
        $fetchSQLString = "SELECT ezcontentobject.id
                           FROM
                               ezcontentobject
                           WHERE
                               ezcontentobject.id='$contentObjectIdentifierAsInt' OR
                               ezcontentobject.remote_id='$contentObjectIdentifier'";
        $resArray = eZDB::instance()->arrayQuery( $fetchSQLString );
        if ( count( $resArray ) == 1 && $resArray !== false )
        {
            return $resArray[0]['id'];
        }
        else
        {
            throw new NotFoundException( $contentObjectIdentifier );
        }
    }

    protected static function getCacheManager( $contentObjectId, $type )
    {
        $cacheFile = $contentObjectId . '.cache';
        $extraPath = eZDir::filenamePath( $contentObjectId );
        $cacheFilePath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata', $type, $extraPath, $cacheFile ) );
        return eZClusterFileHandler::instance( $cacheFilePath );
    }

    public function clearCache( $contentObjectId, $type = 'content' )
    {
        $this->getCacheManager( $contentObjectId, $type )->purge();
    }

    public static function retrieveCache( $file, $mtime, $contentObjectId )
    {
        $content = include( $file );
        return $content;
    }

    public static function generateCache( $file, $contentObjectId )
    {
        $contentObject = parent::findContent( $contentObjectId );
        $content = Content::createFromEzContentObject( $contentObject );;
        return array( 'content'  => $content,
                      'scope'    => 'ocopendata-cache',
                      'datatype' => 'php',
                      'store'    => true );

    }
}