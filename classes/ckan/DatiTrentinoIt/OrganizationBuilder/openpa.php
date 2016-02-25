<?php

namespace Opencontent\Ckan\DatiTrentinoIt\OrganizationBuilder;

use Opencontent\Ckan\DatiTrentinoIt\Organization;
use OcOpendataOrganizationBuilderInterface;
use eZDB;
use eZSiteData;

class OpenPA implements OcOpendataOrganizationBuilderInterface
{

    public function build()
    {
        $instance = \OpenPAInstance::current();

        if ( !$instance->isLive() ){
            throw new \Exception( "L'istanza corrente non è in produzione" );
        }

        if ( $instance->getType() != 'comune_standard' || $instance->getType() != 'comune_new_design' ){
            throw new \Exception( "L'istanza corrente non è un comune" );
        }

        $pagedata = new \OpenPAPageData();
        $contacts = $pagedata->getContactsData();
        $title = \eZINI::instance()->variable( 'SiteSettings', 'SiteName' );

        $trans = \eZCharTransform::instance();
        $name = $trans->transformByGroup( $title, 'identifier' );
        $description = "Dati di cui è titolare il " . $title;

        //$imageUrl = 'http://' . rtrim( \eZINI::instance()->variable( 'SiteSettings', 'SiteURL' ), '/' ) . '/extension/ocopendata/design/standard/images/comunweb-cloud.png';
        $imageUrl = null;

        if ( !isset($contacts['web']) ){
            $contacts['web'] = 'http://' . rtrim( \eZINI::instance()->variable( 'SiteSettings', 'SiteURL' ), '/' );
        }

        $extras = array();
        foreach( $contacts as $key => $value ){
            $extras[] = array(
                'key' => $key,
                'value' => $value
            );
        }

        $org = new Organization();
        $org->name = $name;
        $org->title = $title;
        $org->description = $description;
        $org->image_url = $imageUrl;
        $org->extras = $extras;
        return $org;
    }

    public function storeOrganizationPushedId( $returnData )
    {
        $id = is_array($returnData) ? $returnData['id'] : $returnData->id;
        if ( $id ){
            eZDB::instance()->query( "INSERT INTO ezsite_data ( name, value ) values( 'ckan_organization_id', '$id' )" );
        }
    }

    public function getStoresOrganizationId()
    {
        $data = eZSiteData::fetchByName( 'ckan_organization_id' );
        if ( $data instanceof eZSiteData )
            return $data->attribute( 'value' );
        return null;
    }

    public function removeStoresOrganizationId()
    {
        $data = eZSiteData::fetchByName('ckan_organization_id');
        if ($data instanceof eZSiteData) {
            $data->remove();
        }
    }
}