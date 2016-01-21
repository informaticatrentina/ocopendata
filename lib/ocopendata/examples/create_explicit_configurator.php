<?php

require '../src/Opencontent/Opendata/Rest/Client/HttpClient.php';

use Opencontent\Opendata\Rest\Client\HttpClient;

try
{
    $client = new HttpClient( 'http://openpa.opencontent.it', 'user', 'password' );
    $data = array(
        'client_configurator' => array(
            'class_identifier' => 'article',
            'remote_id_prefix' => 'my_company',
            'images_parent_node' => 43,
            'files_parent_node' => 57,
            'multimedia_parent_node' => 57,
        ),
        'metadata' => array(
            'remote_id' => 'my_own_unique_id',
            'parent_node_id' => 2,
            'section_identifier' => 'standard',
            'status_identifier' => array( 'bar.foo' ),
            'visibility' => 'show'
        ),
        'data' => array(
            'title' => 'Hello world',
            'description' => '<p>Hi everybody!</p>',
            'image' => array(
                'url' => 'https://www.google.it/images/branding/googlelogo/1x/googlelogo_color_272x92dp.png',
                'filename' => 'logo.png',
                'alt' => 'Logo di Google'
            )
        )
    );
    $response = $client->create( $data );
    echo $response->result;
}
catch( Exception $e )
{
    echo $e->getMessage();
}