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

    public static $debug = false;

    protected function getExtras()
    {
        $extras = parent::getExtras();
        $extras[] = array(
            'key' => 'Generator',
            'value' => 'http://www.comunweb.it'
        );
        $geoNameId = self::getGeoNamesId($this->getCustomField('Copertura Geografica'));
        if ($geoNameId) {
            $extras[] = array(
                'key' => 'Copertura Geografica URI',
                'value' => 'http://www.geonames.org/' . $geoNameId
            );
        }

        return $extras;
    }

    public static function getGeoNamesId($string)
    {
        if (!empty($string)) {
            $geonameId = self::getStoredGeonameById( $string );
            if (!$geonameId){
                $searchUri = "http://api.geonames.org/search?username=" . self::$geoUserName . "&q=" . $string;
                try {
                    $data = \OpenPABase::getDataByURL( $searchUri );
                    $searchXML = new SimpleXMLElement($data);
                    if ((int)$searchXML->totalResultsCount > 0){
                        foreach($searchXML->geoname as $geoname ){
                            if ((string)$geoname->countryCode == 'IT' && (string)$geoname->fcode == 'ADM3'){

                                $trans = \eZCharTransform::instance();
                                $name = $trans->transformByGroup($geoname->name, 'urlalias');
                                $stringTransformed = $trans->transformByGroup($string, 'urlalias');
                                if ( $string == $name ){
                                    if ( self::$debug ){
                                        \eZCLI::instance()->output($string . ' -> ', false);
                                        \eZCLI::instance()->output($geoname->name . ' ', false);
                                        \eZCLI::instance()->output($geoname->geonameId);
                                    }
                                    $geonameId = (string)$geoname->geonameId;
                                    self::getStoredGeonameById( $string, $geonameId );
                                    break;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $geonameId = null;
                }
            }
            return $geonameId;
        }
        if ( self::$debug ){
            \eZCLI::instance()->output($string . ' NOTFOUND', false);
        }
        return null;
    }

    protected static function getStoredGeonameById( $key, $value = null )
    {
        $data = array();

        $siteData = eZSiteData::fetchByName( 'geonames_id' );
        if ( !$siteData instanceof eZSiteData ){
            $emptyData = json_encode(array());
            eZDB::instance()->query( "INSERT INTO ezsite_data ( name, value ) values( 'geonames_id', '$emptyData' )" );
            $siteData = eZSiteData::fetchByName( 'geonames_id' );
            if ( self::$debug ){
                \eZCLI::instance()->output('(new)', false);
            }
        }

        if ( $siteData instanceof eZSiteData ){
            $data = json_decode( $siteData->attribute('value'), 1);
        }

        if ( $value !== null ){
            $data[] = array( $key => $value );
            $jsonData = json_encode($data);
            eZDB::instance()->query( "INSERT INTO ezsite_data ( name, value ) values( 'geonames_id', '$jsonData' )" );
            $siteData = eZSiteData::fetchByName( 'geonames_id' );
            $data = json_decode( $siteData->attribute('value'), 1);
            if ( self::$debug ){
                \eZCLI::instance()->output('(stored)', false);
            }
        }

        if ( isset( $data[$key] ) )
            return $data[$key];
        return null;
    }
}