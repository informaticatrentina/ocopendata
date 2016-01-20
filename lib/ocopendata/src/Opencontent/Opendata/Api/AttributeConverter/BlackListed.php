<?php

namespace Opencontent\Opendata\Api\AttributeConverter;


class BlackListed extends Base
{
    public function isPublic()
    {
        return false;
    }

    public function getValue()
    {
        return null;
    }

    public function setValue( $data )
    {
        return null;
    }
}