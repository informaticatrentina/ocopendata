<?php


class DatatableEnvironmentSettings extends DefaultEnvironmentSettings
{
    public function filterSearchResult(
        \Opencontent\Opendata\Api\Values\SearchResults $searchResults,
        \ArrayObject $query,
        \Opencontent\QueryLanguage\QueryBuilder $builder
    ) {

        return array(
            'draw' => (int)( $this->request->get['draw']++ ),
            'recordsTotal' => (int)$searchResults->totalCount,
            'recordsFiltered' => (int)$searchResults->totalCount,
            'data' => $searchResults->searchHits,
            'facets' => $searchResults->facets,
        );
    }

    public function filterQuery(\ArrayObject $query, \Opencontent\QueryLanguage\QueryBuilder $builder)
    {
        $parameters = $this->request->get;
        if (isset( $parameters['length'] )) {
            $query['SearchLimit'] = $parameters['length'];
        }
        if (isset( $parameters['start'] )) {
            $query['SearchOffset'] = $parameters['start'];
        }


        return parent::filterQuery($query, $builder);
    }
}