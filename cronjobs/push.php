<?php

$cli = eZCLI::instance();

$user = eZUser::fetchByName('admin');
eZUser::setCurrentlyLoggedInUser( $user, $user->attribute( 'contentobject_id' ) );

$tools = new OCOpenDataTools();
$nodes = $tools->getDatasetNodes();

$count = count( $nodes );
$cli->notice( "Pushing $count objects" );

$output = new ezcConsoleOutput();
$progressBarOptions = array(
                    'emptyChar'         => ' ',
                    'barChar'           => '='
                );
//$progressBar = new ezcConsoleProgressbar( $output, intval( $count ), $progressBarOptions );
//$progressBar->start();

foreach ( $nodes as $item )
{            
    //$progressBar->advance();    
    try
    {        
        $tools = new OCOpenDataTools();
        $cli->notice( 'Push "' . $item->attribute( 'name' ) . '"' );
        $tools->pushObject( $item->attribute( 'object' ) );        
    }
    catch( Exception $e )
    {
        $error = $e->getMessage();
        $cli->error( $error );
        //print_r( $tools->getDatasetFromObject( $item->attribute( 'object' ) ) );
        
    }
}
//$progressBar->finish();