<?php

require '../src/Opencontent/Opendata/Rest/Client/HttpClient.php';

use OpenContent\Opendata\Rest\Client\HttpClient;

try
{
    $client = new HttpClient( 'http://openpa.opencontent.it', 'user', 'password' );
    $data = array(
        'client_configurator' => 'my_custom_comunweb_client_configurator_identifier',
        'metadata' => array(
            'remote_id' => 'my_own_unique_id' // id => 123456
        )
    );
    $response = $client->update( $data );
    $response = $client->delete( $data );
    echo $response->result;
}
catch( Exception $e )
{
    echo $e->getMessage();
}