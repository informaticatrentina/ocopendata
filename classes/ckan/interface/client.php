<?php

interface OcOpenDataClientInterface
{

    public function pushDataset( $data );

    public function getDataset( $datasetId );

    public function pushOrganization( $data );

    public function getOrganization( $organizationId );

    public function getLicenseList();
}