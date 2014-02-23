<?php
$Module = $Params['Module'];
$ObjectID = $Params['ObjectID'];
try
{
    $object = eZContentObject::fetch( $ObjectID );
    $tools = new OCOpenDataTools();
    $tools->pushObject( $ObjectID );
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
        $data = $tools->getDatasetFromObject( $ObjectID );
        echo '<pre>';
        print_r( $data );
        echo '</pre>';
    }
    catch( Exception $e )
    {
        echo '<em>Conversione in dataset non riuscita</em>';
    }

    eZDisplayDebug();
    eZExecution::cleanExit();
}



?>