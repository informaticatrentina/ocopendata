<?php

use Opencontent\Ckan\DatiTrentinoIt\Organization;

class OcOpendataOrganizationBuilder implements OcOpendataOrganizationBuilderInterface
{

    public function build()
    {
        $org = new Organization();
        $org->name = 'testpencontent';
        $org->title = 'Opencontent Test2';
        $org->description = 'Account di test2 per OpenContent';
        $org->image_url = 'http://www.opencontent.it/logo-oc.png';
        $org->extras = array( array( 'key' => 'foo', 'value' => 'bar' ) );
        return $org;
    }

    public function storeOrganizationPushedId( $returnData )
    {
        if ( $returnData->id ){
            eZDB::instance()->query( 'INSERT INTO ezsite_data ( name, value ) values( \'ckan_organization_id\', \'' . $returnData->id . '\' )' );
        }
    }

    public function getStoresOrganizationId()
    {
        return eZSiteData::fetchByName( 'ckan_organization_id' );
    }
}