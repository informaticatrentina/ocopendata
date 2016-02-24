<?php

interface OcOpendataOrganizationBuilderInterface
{
    public function build();

    public function storeOrganizationPushedId($returnData);

    public function getStoresOrganizationId();
}