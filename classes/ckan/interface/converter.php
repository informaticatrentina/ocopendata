<?php

interface OcOpenDataConverterInterface
{
    public function getDatasetFromObject( eZContentObject $object );

    public function getRemotePrefix();

    public function markObjectPushed( eZContentObject $object, $returnData );

    public function setOrganizationBuilder( OcOpendataOrganizationBuilderInterface $builder );
}