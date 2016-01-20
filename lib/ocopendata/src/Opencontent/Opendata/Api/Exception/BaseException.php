<?php

namespace Opencontent\Opendata\Api\Exception;

use Exception;

abstract class BaseException extends Exception
{
    public function getServerErrorCode()
    {
        return 500;
    }

    public function getErrorCode()
    {
        return 0;
    }
}