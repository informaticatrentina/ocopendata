<?php

namespace Opencontent\Opendata\Api;

use Opencontent\Opendata\Api\Gateway\FileSystem;
use Opencontent\Opendata\Api\Gateway\SolrStorage;
use Opencontent\Opendata\Api\QueryLanguage\EzFind\QueryBuilder as EzFindQueryBuilder;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\Opendata\Api\Values\ContentData;
use Opencontent\Opendata\Api\Values\Metadata;
use Opencontent\Opendata\Api\Values\SearchResults;
use Exception;
use eZSolr;
use ezfSearchResultInfo;

class ContentSearch
{
    protected $query;

    /**
     * @var EnvironmentSettings
     */
    protected $currentEnvironmentSettings;

    public function setEnvironment( EnvironmentSettings $environmentSettings )
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    public function search( $query )
    {
        $builder = new EzFindQueryBuilder();
        $queryObject = $builder->instanceQuery( $query );
        $ezFindQuery = $queryObject->convert();

        if ( $ezFindQuery instanceof \ArrayObject )
            $ezFindQuery = $ezFindQuery->getArrayCopy();
        else
            throw new \RuntimeException( "Query builder did not return a valid query" );

        //$ezFindQuery['Filter'][] = ezfSolrDocumentFieldBase::generateMetaFieldName('installation_id') . ':' . eZSolr::installationID();
        $ezFindQuery['AsObjects'] = false;
        $ezFindQuery['FieldsToReturn'] = array( SolrStorage::getSolrIdentifier() );

        $solr = new eZSolr();
        $rawResults = $solr->search(
            $ezFindQuery['_query'],
            $ezFindQuery
        );

        if ( $rawResults['SearchExtras'] instanceof ezfSearchResultInfo )
        {
            if ( $rawResults['SearchExtras']->attribute( 'hasError' ) )
            {
                $error = $rawResults['SearchExtras']->attribute( 'error' );
                if ( is_array( $error ) ) $error = (string)$error['msg'];
                throw new \RuntimeException( $error );
            }
        }

        $searchResults = new SearchResults();
        if ( $this->currentEnvironmentSettings->__get( 'debug' ) == true )
        {
            $searchResults->query = array(
                'string' => (string)$queryObject,
                'eZFindQuery' => $ezFindQuery
            );

            if ( $rawResults['SearchExtras'] instanceof ezfSearchResultInfo )
            {
                $searchResults->query['responseHeader'] = $rawResults['SearchExtras']->attribute( 'responseHeader' );
            }
        }
        else
        {
            $searchResults->query = (string)$queryObject;
        }

        $searchResults->totalCount = (int) $rawResults['SearchCount'];

        $fileSystemGateway = new FileSystem();
        $contentRepository = new ContentRepository();
        $contentRepository->setEnvironment( $this->currentEnvironmentSettings );

        foreach( $rawResults['SearchResult'] as $resultItem )
        {
            try
            {
                if ( isset( $resultItem['data_map']['opendatastorage'] ) )
                {
                    $contentArray = $resultItem['data_map']['opendatastorage'];
                    $content = new Content();
                    $content->metadata = new Metadata( (array)$contentArray['metadata'] );
                    $content->data = new ContentData( (array)$contentArray['data'] );
                }
                else
                {
                    $id = isset( $resultItem['meta_id_si'] ) ? $resultItem['meta_id_si'] : isset( $resultItem['id_si'] ) ? $resultItem['id_si'] : $resultItem['id'];
                    $content = $fileSystemGateway->loadContent( $id );
                }

                $content = $contentRepository->read( $content );
            }
            catch( Exception $e )
            {
                $content = new Content();
                $content->metadata = new Metadata( array( 'id' => $e->getMessage() ) );
                $content->data = new ContentData( array( 'rawresult' => $resultItem ) );
            }

            $searchResults->searchHits[] = $content->jsonSerialize();
        }

        return $searchResults;
    }
}