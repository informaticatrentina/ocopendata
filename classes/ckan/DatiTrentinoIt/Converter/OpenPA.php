<?php

namespace Opencontent\Ckan\DatiTrentinoIt\Converter;

use Opencontent\Ckan\DatiTrentinoIt\Converter;

class OpenPA extends Converter
{
    protected function getExtras()
    {
        $extras = parent::getExtras();
        $extras[] = array(
            'key' => 'Generator',
            'value' => 'http://www.comunweb.it'
        );
        return $extras;
    }
}