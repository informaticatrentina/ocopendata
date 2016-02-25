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
        $pagedata = new \OpenPAPageData();
        $contacts = $pagedata->getContactsData();
        $title = \eZINI::instance()->variable( 'SiteSettings', 'SiteName' );

        $trans = \eZCharTransform::instance();
        $name = $trans->transformByGroup( $title, 'identifier' );
        $description = "Dati di cui è titolare il " . $title;

        $imageUrl = 'http://' . rtrim( \eZINI::instance()->variable( 'SiteSettings', 'SiteURL' ), '/' ) . '/extension/ocopendata/design/standard/images/comunweb-cloud.png';

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