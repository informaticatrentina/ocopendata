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
use ezfSolrDocumentFieldBase;
use ArrayObject;

class ContentSearch
{
    /**
     * @var string
     */
    protected $query;

    /**
     * @var EnvironmentSettings
     */
    protected $currentEnvironmentSettings;

    public function setEnvironment(EnvironmentSettings $environmentSettings)
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    public function search($query, array $limitation = null)
    {
        $this->query = $query;

        $builder = new EzFindQueryBuilder();
        $queryObject = $builder->instanceQuery($query);
        $ezFindQueryObject = $queryObject->convert();

        if (!$ezFindQueryObject instanceof ArrayObject) {
            throw new \RuntimeException("Query builder did not return a valid query");
        }

        $ezFindQueryObject = $this->currentEnvironmentSettings->filterQuery($ezFindQueryObject, $builder);
        $ezFindQuery = $ezFindQueryObject->getArrayCopy();

        //$ezFindQuery['Filter'][] = ezfSolrDocumentFieldBase::generateMetaFieldName('installation_id') . ':' . eZSolr::installationID();
        $ezFindQuery['Limitation'] = $limitation;
        $ezFindQuery['AsObjects'] = false;
        $ezFindQuery['FieldsToReturn'] = array(SolrStorage::getSolrIdentifier());

        $solr = new eZSolr();
        $rawResults = @$solr->search(
            $ezFindQuery['_query'],
            $ezFindQuery
        );
        if ($rawResults['SearchExtras'] instanceof ezfSearchResultInfo) {
            if ($rawResults['SearchExtras']->attribute('hasError')) {
                $error = $rawResults['SearchExtras']->attribute('error');
                if (is_array($error)) {
                    $error = (string)$error['msg'];
                }
                throw new \RuntimeException($error);
            }
        }

        $searchResults = new SearchResults();
        if ($this->currentEnvironmentSettings->__get('debug') == true) {
            $searchResults->query = array(
                'string' => (string)$queryObject,
                'eZFindQuery' => $ezFindQuery
            );

            if ($rawResults['SearchExtras'] instanceof ezfSearchResultInfo) {
                $searchResults->query['responseHeader'] = $rawResults['SearchExtras']->attribute(
                    'responseHeader'
                );
            }
        } else {
            $searchResults->query = (string)$queryObject;
        }

        $searchResults->totalCount = (int)$rawResults['SearchCount'];

        if (($ezFindQuery['SearchLimit'] + $ezFindQuery['SearchOffset']) < $searchResults->totalCount) {
            $nextPageQuery = clone $queryObject;
            $nextPageQuery->setParameter('offset', ($ezFindQuery['SearchLimit'] + $ezFindQuery['SearchOffset']));
            $searchResults->nextPageQuery = (string)$nextPageQuery;
        }

        $fileSystemGateway = new FileSystem();
        $contentRepository = new ContentRepository();
        $contentRepository->setEnvironment($this->currentEnvironmentSettings);

        foreach ($rawResults['SearchResult'] as $resultItem) {
            $id = isset($resultItem['meta_id_si']) ? $resultItem['meta_id_si'] : isset($resultItem['id_si']) ? $resultItem['id_si'] : $resultItem['id'];
            try {
                if (isset($resultItem['data_map']['opendatastorage'])) {
                    $contentArray = $resultItem['data_map']['opendatastorage'];
                    $content = new Content();
                    $content->metadata = new Metadata((array)$contentArray['metadata']);
                    $content->data = new ContentData((array)$contentArray['data']);
                } else {
                    $content = $fileSystemGateway->loadContent((int)$id);
                }

                $content = $contentRepository->read($content);
                $filterFields = isset($ezFindQuery['_filterFields']) ? $ezFindQuery['_filterFields'] : null;
                $filterLanguages = isset($ezFindQuery['_filterLanguages']) ? $ezFindQuery['_filterLanguages'] : null;
                if ($filterFields !== null) {
                    $content = $this->filterFields($content, $filterFields, $filterLanguages);
                } else {
                    $content = $this->filterLanguages($content, $filterLanguages);
                }
                $searchResults->searchHits[] = $content;
            } catch (Exception $e) {
                $content = new Content();
                $content->metadata = new Metadata(array('id' => $id));
                $content->data = new ContentData(
                    array(
                        '_error' => $e->getMessage(),
                        '_rawresult' => $resultItem
                    )
                );
            }
        }

        if (isset($ezFindQuery['Facet'])
            && is_array($ezFindQuery['Facet'])
            && !empty($ezFindQuery['Facet'])
            && $rawResults['SearchExtras'] instanceof ezfSearchResultInfo
        ) {
            $facets = array();
            $facetResults = $rawResults['SearchExtras']->attribute('facet_fields');
            foreach ($ezFindQuery['Facet'] as $index => $facetDefinition) {
                $facetResult = $facetResults[$index];
                $facets[] = array(
                    'name' => $facetDefinition['name'],
                    'data' => $facetResult['countList']
                );
            }
            $searchResults->facets = $facets;
        }

        return $this->currentEnvironmentSettings->filterSearchResult($searchResults, $ezFindQueryObject, $builder);
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return EnvironmentSettings
     */
    public function getCurrentEnvironmentSettings()
    {
        return $this->currentEnvironmentSettings;
    }

    /**
     * @param EnvironmentSettings $currentEnvironmentSettings
     */
    public function setCurrentEnvironmentSettings($currentEnvironmentSettings)
    {
        $this->currentEnvironmentSettings = $currentEnvironmentSettings;
    }


    private function filterFields($content, array $fields, array $languages = null)
    {
        $data = array();
        foreach ($fields as $field) {
            $parts = explode('.', $field);
            if ($parts[0] == 'metadata' && isset($content['metadata'][$parts[1]])) {
                if ($parts[1] == 'name') {
                    if (!$languages) {
                        $languages = array_keys($content['metadata']['name']);
                    }
                    foreach ($content['metadata']['name'] as $language => $name) {
                        if (count($languages) == 1) {
                            $data['name'] = $name;
                        } else {
                            if (!isset($data['name'])) {
                                $data['name'] = array();
                            }
                            $data['name'][$language] = $name;
                        }
                    }
                } else {
                    $data[$parts[1]] = $content['metadata'][$parts[1]];
                }
            } elseif ($parts[0] == 'data') {

                if (!$languages) {
                    $languages = array_keys($content['data']);
                }

                foreach ($content['data'] as $language => $languageData) {
                    if (in_array($language, $languages)) {
                        if (isset($languageData[$parts[1]])) {
                            if (count($languages) == 1) {
                                $data[$parts[1]] = $languageData[$parts[1]];
                            } else {
                                if (!isset($data[$parts[1]])) {
                                    $data[$parts[1]] = array();
                                }
                                $data[$parts[1]][$language] = $languageData[$parts[1]];
                            }

                        }
                    }
                }
            }
        }

        return $data;
    }

    private function filterLanguages($content, array $languages)
    {
        //@todo
        return $content;
    }
}
