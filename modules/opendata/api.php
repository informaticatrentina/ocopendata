<?php

use Opencontent\Opendata\Api\EnvironmentLoader;
use Opencontent\Opendata\Api\ContentBrowser;
use Opencontent\Opendata\Api\ContentRepository;
use Opencontent\Opendata\Api\ContentSearch;

$Module = $Params['Module'];
$Environment = $Params['Environment'];
$Environment = $Params['Environment'];
$Action = $Params['Action'];
$Param = $Params['Param'];

$Debug = isset( $_GET['debug'] );

try
{
    $contentRepository = new ContentRepository();
    $contentBrowser = new ContentBrowser();
    $contentSearch = new ContentSearch();

    $currentEnvironment = EnvironmentLoader::loadPreset( $Environment );
    $contentRepository->setEnvironment( $currentEnvironment );
    $contentBrowser->setEnvironment( $currentEnvironment );
    $contentSearch->setEnvironment( $currentEnvironment );
    
    $parser = new ezpRestHttpRequestParser();
    $request = $parser->createRequest();
    $currentEnvironment->__set('request', $request);

    $data = array();

    if ( $Action == 'read' )
    {
        $data = (array)$contentRepository->read( $Param );
    }
    elseif ( $Action == 'search' )
    {
        $data = (array)$contentSearch->search( $Param );
    }
}
catch( Exception $e )
{
    $data = array(
        'error_code' => $e->getCode(),
        'error_message' => $e->getMessage()
    );
    if ( $Debug )
    {
        $data['trace'] = $e->getTraceAsString();
    }
}
if ( $Debug )
{
    echo '<pre>';
    print_r( $data );
    echo '</pre>';
    eZDisplayDebug();
}
else
{
    header('Content-Type: application/json');
    echo json_encode( $data );
}

eZExecution::cleanExit();