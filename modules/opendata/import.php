<?php

/** @var eZModule $module */
$module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();

$remoteBaseUrl = $http->hasPostVariable( 'CurrentRemoteBaseUrl' ) ? $http->postVariable( 'CurrentRemoteBaseUrl' ) : false;
$remoteNodeId = $http->hasPostVariable( 'CurrentRemoteNodeId' ) ? $http->postVariable( 'CurrentRemoteNodeId' ) : false;
$localParentNodeId = $http->hasPostVariable( 'CurrentLocalParentNodeId' ) ? $http->postVariable( 'CurrentLocalParentNodeId' ) : false;
$createContentClass = $http->hasPostVariable( 'CreateContentClass' );

if ( $http->hasPostVariable( 'BrowseActionName' ) && $http->postVariable( 'BrowseActionName' ) == 'OcOpenDataLocalParentNodeIdBrowse' )
{
    $selectedArray = $http->postVariable( 'SelectedNodeIDArray' );
    $localParentNodeId = $selectedArray[0];

}
elseif ( $http->hasPostVariable( 'SelectCurrentLocalParentNodeId' ) )
{
    eZContentBrowse::browse(
        array(
            'action_name' => 'OcOpenDataLocalParentNodeIdBrowse',
            'selection' => 'single',
            'return_type' => 'NodeID',
            'start_node' => 1,
            'from_page' => '/opendata/import/',
            'cancel_page' => '/opendata/import/',
            'persistent_data' => array(
                'CurrentRemoteBaseUrl' => $remoteBaseUrl,
                'CurrentRemoteNodeId' => $remoteNodeId
            )
        ),
        $module
    );
    return;
}

$localParentNode = ( $localParentNodeId ) ? eZContentObjectTreeNode::fetch( $localParentNodeId ) : false;
$error = false;

if ( empty( $remoteBaseUrl ) || !is_numeric( $remoteNodeId ) || !$localParentNode instanceof eZContentObjectTreeNode )
{
    $error = "Inserisci tutti i parametri";
}
else
{
    try
    {
        $apiNodeUrl = rtrim( $remoteBaseUrl, '/' );
        $apiNodeUrl .= '/api/opendata/v1/content/node/' . $remoteNodeId;
        $remoteApiNode = OCOpenDataApiNode::fromLink( $apiNodeUrl );
        if ( !$remoteApiNode instanceof OCOpenDataApiNode )
        {
            throw new Exception( "Url remoto \"{$apiNodeUrl}\" non raggiungibile" );
        }

        if ( $createContentClass )
        {
            if ( !class_exists( 'OCClassTools' ) )
            {
                throw new Exception( "Libreria OCClassTools non trovata" );
            }

            $localClass = eZContentClass::fetchByIdentifier( $remoteApiNode->metadata['classIdentifier'] );
            if ( !$localClass instanceof eZContentClass )
            {
                $remoteUrl = $remoteBaseUrl . '/classtools/definition/';
                $classTool = new OCClassTools( $remoteApiNode->metadata['classIdentifier'], true, array(), $remoteUrl );
                $classTool->sync();
            }
        }

        $newObject = $remoteApiNode->createContentObject( $localParentNodeId );
        if ( !$newObject instanceof eZContentObject )
        {
            throw new Exception( "Fallita la creazione dell'oggetto da nodo remoto" );
        }
        else
        {
            $module->redirectTo( $newObject->attribute( 'main_node' )->attribute( 'url_alias' ) );
        }
    }
    catch( Exception $e )
    {
        $error = $e->getMessage();
    }
}

$tpl->setVariable( 'error', $error );
$tpl->setVariable( 'CreateContentClass', $createContentClass );
$tpl->setVariable( 'CurrentLocalParentNode', $localParentNode );
$tpl->setVariable( 'CurrentLocalParentNodeId', $localParentNodeId );
$tpl->setVariable( 'CurrentRemoteBaseUrl', $remoteBaseUrl );
$tpl->setVariable( 'CurrentRemoteNodeId', $remoteNodeId );
$Result = array();
$Result['content'] = $tpl->fetch( 'design:opendata/import/import_from_remote_node_id.tpl' );