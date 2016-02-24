<?php

use Opencontent\Opendata\Api\ContentSearch;

class SearchQueryCSVExporter extends AbstarctExporter
{

    /**
     * @var ContentSearch
     */
    protected $contentSearch;

    protected $queryString;

    protected $CSVheaders;

    public function __construct( $parentNodeId, $queryString )
    {
        print_r($queryString);
        $this->functionName = 'csv';
        if (  $this->checkAccess() !== true )
        {
            throw new Exception( 'Current user can not export csv' );
        }

        $this->ini = eZINI::instance( 'exportas.ini' );
        $this->setOptions( $this->ini->group( 'Settings' ) );

        $currentEnvironment = new CsvEnvironmentSettings;
        $currentEnvironment->__set( 'identifier', 'csv' );
        $currentEnvironment->__set( 'debug', false );
        $this->contentSearch = new ContentSearch();
        $this->contentSearch->setEnvironment( $currentEnvironment );
        $this->queryString = $queryString;
        $this->filename = uniqid('export_');
    }

    function transformNode( eZContentObjectTreeNode $node )
    {
        return null;
    }

    public function fetch()
    {
        return $this->contentSearch->search($this->queryString);
    }

    public function fetchCount()
    {
        $result = $this->contentSearch->search($this->queryString);
        return $result->totalCount;
    }

    function transformItem( $item )
    {
        $language = $item['metadata']['languages'][0];
        $data = $item['data'][$language];
        if ( $this->CSVheaders == null){
            $this->CSVheaders = array_keys( $data );
        }
        $stringData = array();
        foreach( $data as $key => $value ){
            $stringData[$key] = is_string( $value ) ? strip_tags( $value ) : '';
        }
        return $stringData;
    }

    function handleDownload()
    {
        $filename = $this->filename . '.csv';
        header( 'X-Powered-By: eZ Publish' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( "Content-Disposition: attachment; filename=$filename" );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );

        $output = fopen('php://output', 'w');
        $runOnce = false;
        do
        {
            $result = $this->fetch();

            foreach ( $result->searchHits as $item )
            {
                $values = $this->transformItem( $item );
                if ( !$runOnce )
                {
                    fputcsv( $output, array_values( $this->CSVheaders ), $this->options['CSVDelimiter'], $this->options['CSVEnclosure'] );
                    $runOnce = true;
                }
                fputcsv( $output, $values, $this->options['CSVDelimiter'], $this->options['CSVEnclosure'] );
                flush();
            }
            $this->queryString = $result->nextPageQuery;

        } while ( $this->queryString != null );
    }
}