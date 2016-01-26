<?php

$module = $Params['Module'];
$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();
$data = null;

if ( $http->hasGetVariable( 'query' ) )
{
    $query = urldecode( $http->getVariable( 'query' ) );

    try
    {
        $factory = new \Opencontent\Opendata\Api\QueryLanguage\EzFind\QueryBuilder();
        $tokenFactory = $factory->getTokenFactory();
        $converter = new \Opencontent\QueryLanguage\Converter\AnalyzerQueryConverter();
        $parser = new \Opencontent\QueryLanguage\Parser(
            new \Opencontent\QueryLanguage\Query( $query ),
            $query,
            $tokenFactory
        );

        $query = $parser->parse();
        $converter->setQuery( $query );

        $data = $converter->convert();
    }
    catch ( Exception $e )
    {
        $data = array( 'error' => $e->getMessage() );
    }
}
header('Content-Type: application/json');
echo json_encode( $data );
eZExecution::cleanExit();