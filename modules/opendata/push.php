<?php
/** @var eZModule $Module */
$Module = $Params['Module'];
$ObjectID = $Params['ObjectID'];


try
{
    $object = eZContentObject::fetch( $ObjectID );
    $tools = new OCOpenDataTools();
    $data = $tools->pushObject( $ObjectID );
    echo '<pre>';
    print_r( $data );
    eZDisplayDebug();
    eZExecution::cleanExit();
    $Module->redirectTo( $object->attribute( 'main_node' )->attribute( 'url_alias' ) . '/(message)/Dataset inviato' );
}
catch( Exception $e )
{
    $error = $e->getMessage();
    echo "<h1>$error</h1>";
    
    try
    {
        $object = eZContentObject::fetch( $ObjectID );
        $tools = new OCOpenDataTools();
        $data = $tools->getDatasetFromObject( $object );
        echo '<pre>';
        print_r( $data );
        echo '</pre>';
    }
    catch( Exception $error )
    {
        echo "<em>Conversione in dataset non riuscita</em> ({$error->getMessage()})";
    }

    eZDisplayDebug();
    eZExecution::cleanExit();
}



?>