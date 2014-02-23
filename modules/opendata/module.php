<?php

$Module = array( 'name' => 'OpenData' );

$ViewList = array();
$ViewList['push'] = array(
    'functions' => array( 'push' ),
    'script' => 'push.php',
    'params' => array( 'ObjectID' ),
    'unordered_params' => array()
);
$ViewList['view'] = array(
    'functions' => array( 'view' ),
    'script' => 'view.php',
    'params' => array( 'ObjectID' ),
    'unordered_params' => array()
);

$FunctionList = array();
$FunctionList['push'] = array();
$FunctionList['view'] = array();
?>
