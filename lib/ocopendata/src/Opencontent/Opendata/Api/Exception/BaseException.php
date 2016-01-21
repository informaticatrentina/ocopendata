<?php

namespace Opencontent\Opendata\Api\Exception;

use Exception;

class BaseException extends Exception
{
    public function getServerErrorCode()
    {
        return 500;
    }

    public function getErrorCode()
    {
        return get_called_class();
    }
}