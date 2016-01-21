<?php

namespace Opencontent\Opendata\Api\Exception;

use Opencontent\Opendata\Api\Exception\BaseException;

class InvalidInputException extends BaseException
{
    public function __construct( $message, $identifier, $value = array() )
    {
        //@todo
        parent::__construct( $identifier );
    }
}