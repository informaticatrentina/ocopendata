<?php
$Module = $Params['Module'];
$ObjectID = $Params['ObjectID'];

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

?>