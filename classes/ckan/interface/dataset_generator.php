<?php

interface OcOpendataDatasetGeneratorInterface
{
    /**
     * @param $classIdentifier
     * @param bool $dryRun
     *
     * @return eZContentObject
     */
    public function createFromClassIdentifier( $classIdentifier, $dryRun = null );
}