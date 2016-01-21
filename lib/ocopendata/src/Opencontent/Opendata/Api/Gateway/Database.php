<?php

namespace Opencontent\Opendata\Api\Gateway;
use Opencontent\Opendata\Api\Gateway;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\Exception\NotFoundException;
use Opencontent\Opendata\Api\Exception\ForbiddenException;
use eZContentObject;

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
        $contentObject = self::findContent( $contentObjectIdentifier );
        $content = Content::createFromEzContentObject( $contentObject );
        return $content;
    }

    /**
     * @param $contentObjectIdentifier
     *
     * @return eZContentObject|null
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    protected static function findContent( $contentObjectIdentifier )
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