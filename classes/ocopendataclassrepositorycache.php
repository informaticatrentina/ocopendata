<?php

class OCOpenDataClassRepositoryCache
{
    public static function clearCache()
    {
        $commonPath = eZDir::path( array( eZSys::cacheDirectory(), 'ocopendata' ) );
        $fileHandler = eZClusterFileHandler::instance();
        $commonSuffix = '';
        $fileHandler->fileDeleteByDirList( array('class'), $commonPath, $commonSuffix );
    }
}
