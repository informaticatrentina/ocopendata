<?php


class DatatableEnvironmentSettings extends DefaultEnvironmentSettings
{
    public function filterSearchResult(
        \Opencontent\Opendata\Api\Values\SearchResults $searchResults,
        \ArrayObject $query,
        \Opencontent\QueryLanguage\QueryBuilder $builder
    ) {
        return array(
            'draw' => (int)( ++$this->request->get['draw']),
            'recordsTotal' => (int)$searchResults->totalCount,
            'recordsFiltered' => (int)$searchResults->totalCount,
            'data' => $searchResults->searchHits,
            'facets' => $searchResults->facets,
            'query' => $query
        );
    }
    
    protected function filterMetaData( Content $content )
    {
        return $content;
    }

    public function filterQuery(
        \ArrayObject $query,
        \Opencontent\QueryLanguage\QueryBuilder $builder
    ) {
        $parameters = $this->request->get;        
        
        $columns = $parameters['columns'];
        $order = $parameters['order'];
        $search = $parameters['search'];
        
        foreach( $columns as $index => $column ){
            $columns[$index]['fieldNames'] = $builder->getSolrNamesHelper()->generateFieldNames( $column['name'] );
            $columns[$index]['sortNames'] = $builder->getSolrNamesHelper()->generateSortNames( $column['name'] );
            if ( !empty($column['search']['value']) ){
                //@todo
            }            
        }
        
        $query['SortBy'] = array();
        foreach( $order as $orderParam ){
            $column = $columns[$orderParam['column']];
            if ( $column['orderable'] ){
                foreach( $column['sortNames'] as $field){
                    $query['SortBy'][$field] = $orderParam['dir'];
                }
            }
        }
        
        if ( !empty($search['value']) ){
            $query['_query'] = $search['value'];
        }
        
        if (isset($parameters['length'])) {
            $query['SearchLimit'] = $parameters['length'];
        }
        if (isset($parameters['start'])) {
            $query['SearchOffset'] = $parameters['start'];
        }

        return parent::filterQuery($query, $builder);
    }
        
}