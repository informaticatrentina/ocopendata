<?php

namespace Opencontent\Opendata\Api\QueryLanguage\EzFind;

use Opencontent\Opendata\Api\ClassRepository;
use Opencontent\QueryLanguage\QueryBuilder as BaseQueryBuilder;


class QueryBuilder extends BaseQueryBuilder
{
    public $fields = array(
        'q'
    );

    public $metaFields = array(
        'id',
        'remote_id',
        'name',
        'published',
        'modified',
        'section',
        'state',
    );

    public $parameters = array(
        'sort',
        'limit',
        'offset',
        'classes',
        'subtree'
    );

    public $operators = array(
        '=',
        '!=',
        'in',
        '!in',
        'contains',
        '!contains',
        'range',
        '!range'
    );

    public $functionFields = array(
        'calendar',
        'raw'
    );

    public function __construct()
    {
        $classRepository = new ClassRepository();
        $availableFieldDefinitions = $classRepository->listAttributesGroupedByIdentifier();

//        echo '<pre>';
//        print_r( $availableFieldDefinitions );
//        die();

        $this->fields = array_merge(
            $this->fields,
            array_keys( $availableFieldDefinitions )
        );

        $this->tokenFactory = new TokenFactory(
            $this->fields,
            $this->metaFields,
            $this->functionFields,
            $this->operators,
            $this->parameters,
            $this->clauses
        );

        $sensorHelper = new SolrNamesHelper( $availableFieldDefinitions, $this->tokenFactory );

        $sentenceConverter = new SentenceConverter( $sensorHelper );

        $parameterConverter = new ParameterConverter( $sensorHelper );

        $this->converter = new QueryConverter(
            $sentenceConverter,
            $parameterConverter
        );

    }

}