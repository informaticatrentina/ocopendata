<?php

$module = $Params['Module'];
$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();

$query = null;
$error = null;
$tokens = array();

if ( $http->hasGetVariable( 'query' ) )
{
    $query = $http->getVariable( 'query' );
}

try
{
    $factory = new \Opencontent\Opendata\Api\QueryLanguage\EzFind\QueryBuilder();
    $tokenFactory = $factory->getTokenFactory();
    $fields = $factory->fields;
    $metaFields = $factory->metaFields;
    $operators = $factory->operators;
    $parameters = $factory->parameters;
    $tokens = array_merge( $fields, $parameters, $operators );
    sort( $tokens );

}
catch ( Exception $e )
{
    $error = $e->getMessage();
}

$tpl->setVariable( 'error', $error );
$tpl->setVariable( 'query', $query );
$tpl->setVariable( 'tokens', $tokens );

echo $tpl->fetch( 'design:opendata/console.tpl' );
eZDisplayDebug();
eZExecution::cleanExit();