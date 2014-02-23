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
    echo $error;
    eZDisplayDebug();
    eZExecution::cleanExit();
}



?>