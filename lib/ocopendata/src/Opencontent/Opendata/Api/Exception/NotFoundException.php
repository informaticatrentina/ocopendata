<?php

namespace Opencontent\Opendata\Api\Exception;

use Opencontent\Opendata\Api\Exception\BaseException;

class NotFoundException extends BaseException
{
    public function __construct( $contentObjectIdentifier )
    {
        parent::__construct( "Content {$contentObjectIdentifier} not found" );
    }

    public function getServerErrorCode()
    {
        return 404;
    }
}