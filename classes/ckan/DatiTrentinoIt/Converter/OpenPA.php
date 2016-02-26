<?php

namespace Opencontent\Ckan\DatiTrentinoIt\Converter;

use Opencontent\Ckan\DatiTrentinoIt\Converter;
use SimpleXMLElement;
use Exception;
use eZDB;
use eZSiteData;

class OpenPA extends Converter
{
    public static $geoUserName = 'lucarealdi';

    protected function getExtras()
    {
        $extras = parent::getExtras();
        $extras[] = array(
            'key' => 'Generator',
            'value' => 'http://www.comunweb.it'
        );
        $geoNames = \eZINI::instance('geonames.ini')->hasGroup('GeoNamesId') ? \eZINI::instance('geonames.ini')->group('GeoNamesId') : array();
        $instanceId = \OpenPAInstance::current()->getIdentifier();
        if (isset($geoNames[$instanceId])) {
            $extras[] = array(
                'key' => 'Copertura Geografica URI',
                'value' => 'http://www.geonames.org/' . $geoNames[$instanceId]
            );
        }

        return $extras;
    }
}