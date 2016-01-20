<?php

namespace OpenContent\Opendata\Api\Cache;

use OpenContent\Opendata\Api\ContentRepository as BaseContentRepository;
use Opencontent\Opendata\Api\Values\Content;
use eZContentObject;
use eZContentLanguage;
use eZDir;
use eZSys;
use eZClusterFileHandler;

class ContentRepository extends BaseContentRepository
{

    public function loadContent( eZContentObject $contentObject )
    {
        $contentObjectIdentifier = $contentObject->attribute( 'id' );
        return $this->getCacheManager( $contentObjectIdentifier )->processCache(
            array( 'OpenContent\Opendata\Api\Cache\ContentRepository', 'retrieveCache' ),
            array( 'OpenContent\Opendata\Api\Cache\ContentRepository', 'generateCache' ),
            null,
            null,
            $contentObjectIdentifier
        );
    }

    protected function getCacheManager( $contentObjectIdentifier )
    {
        $cacheFile = $contentObjectIdentifier . '.cache';
        $language = $this->getCurrentLanguage();
        $extraPath = eZDir::filenamePath( $contentObjectIdentifier );
        $cacheFilePath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata', $language, 'content', $extraPath, $cacheFile ) );
        return eZClusterFileHandler::instance( $cacheFilePath );
    }

    public static function clearCache( $contentObjectIdentifier )
    {
        $languages = eZContentLanguage::fetchLocaleList();
        if ( !empty( $languages ) )
        {
            $commonPath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata' ) );
            $fileHandler = eZClusterFileHandler::instance();
            $commonSuffix = "content/" . eZDir::filenamePath( $contentObjectIdentifier );
            $fileHandler->fileDeleteByDirList( $languages, $commonPath, $commonSuffix );
        }
    }

    public static function retrieveCache( $file, $mtime, $contentObjectIdentifier )
    {
        $content = include( $file );
        return $content;
    }

    public static function generateCache( $file, $contentObjectIdentifier )
    {
        $repository = new BaseContentRepository();
        $content = $repository->read( $contentObjectIdentifier );
        return array( 'content'  => $content,
                      'scope'    => 'ocopendata-cache',
                      'datatype' => 'php',
                      'store'    => true );

    }
}