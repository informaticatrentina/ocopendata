<?php

$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array( 'script' => 'extension/ocopendata/autoloads/ocopendataoperators.php',
                                    'class' => 'OCOpenDataOperators',
                                    'operator_names' => array_keys( OCOpenDataOperators::$operators ) );

?>