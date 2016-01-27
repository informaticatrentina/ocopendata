<?php

namespace Opencontent\Opendata\Api\QueryLanguage\EzFind;

use Opencontent\Opendata\Api\ClassRepository;
use Opencontent\Opendata\Api\StateRepository;
use Opencontent\Opendata\Api\SectionRepository;
use Opencontent\QueryLanguage\Parser\Sentence;
use Opencontent\QueryLanguage\Converter\Exception;
use ArrayObject;
use eZINI;
use eZSolr;
use ezfSolrDocumentFieldName;
use ezfSolrDocumentFieldBase;

class SentenceConverter
{
    /**
     * @var ArrayObject
     */
    protected $convertedQuery;

    /**
     * @var array[]
     */
    protected $availableFieldDefinitions;

    /**
     * @var ezfSolrDocumentFieldName
     */
    protected $documentFieldName;

    /**
     * @var array
     */
    protected $metaFields;

    /**
     * @var StateRepository
     */
    protected $stateRepository;

    /**
     * @var SectionRepository
     */
    protected $sectionRepository;

    /**
     * @var ClassRepository
     */
    protected $classRepository;

    /**
     * @var bool
     */
    private $availableFieldIsFiltered = false;

    public function __construct( $availableFieldDefinitions, $metaFields )
    {
        $this->availableFieldDefinitions = $availableFieldDefinitions;
        $this->metaFields = $metaFields;
        $this->documentFieldName = new ezfSolrDocumentFieldName();
        $this->stateRepository = new StateRepository();
        $this->sectionRepository = new SectionRepository();
        $this->classRepository = new ClassRepository();
    }

    /**
     * @param ArrayObject $convertedQuery
     */
    public function setCurrentConvertedQuery( ArrayObject $convertedQuery )
    {
        $this->convertedQuery = $convertedQuery;
    }

    /**
     * @param Sentence $sentence
     *
     * @return array|string|null
     * @throws Exception
     */
    public function convert( Sentence $sentence )
    {
        if ( !$this->availableFieldIsFiltered )
        {
            $this->filterAvailableFieldDefinitions();
        }

        $field = (string)$sentence->getField();
        $operator = (string)$sentence->getOperator();
        $value = $sentence->getValue();

        if ( $field == 'q' )
        {
            $this->convertedQuery['_query'] = $sentence->stringValue();
            return null;
        }

        $value = $this->cleanValue( $value );

        switch ( $operator )
        {
            case 'contains':
            case '!contains':
            {
                $data = $this->generateContainsFilter( $field, $value, $operator == '!contains' );
            } break;

            case 'in':
            case '!in':
            {
                $data = $this->generateInFilter( $field, $value, $operator == '!in' );
            } break;

            case 'range':
            case '!range':
            {
                $data = $this->generateRangeFilter( $field, $value, $operator == '!range' );
            } break;

            case '=':
            case '!=':
            {
                $data = $this->generateEqFilter( $field, $value, $operator == '!=' );
            } break;

            default:
                throw new Exception( "Operator $operator not handled" );
        }
        return $data;
    }

    protected function cleanValue( $value )
    {
        if ( is_array( $value ) )
        {
            $data = array();
            foreach( $value as $item )
            {
                $item = str_replace( "\'", "''", $item );
                $data[] = trim( $item, "'" );
            }
        }
        else
        {
            $value = str_replace( "\'", "''", $value );
            $data[] = trim( $value, "'" );
        }
        return $data;
    }

    protected function formatFilterValue( $value, $type )
    {
        switch( $type )
        {
            case 'meta_section';
            case 'meta_state';
            case 'meta_id';
            case 'tint':
            case 'sint':
            case 'int':
                $value = (int) $value;
                break;

            case 'float':
            case 'double':
            case 'sfloat':
            case 'tfloat':
                $value = (float) $value;
                break;

            case 'string':
                $value = '((*' . strtolower( $value ) . '*) OR "' . $value . '"")';
                break;

            case 'sub_string':
                $value = '"' . $value . '"';
                break;

            case 'meta_published':
            case 'meta_modified':
            case 'date':
                $value = '"' . ezfSolrDocumentFieldBase::convertTimestampToDate( strtotime( $value ) ) . '"';
                break;

            case 'meta_section_id':
            {
                $section = $this->sectionRepository->load( $value );
                if ( is_array( $section ) )
                {
                    $value = (int) $section['id'];
                }
            } break;

            case 'meta_object_states':
            {
                $state = $this->stateRepository->load( $value );
                if ( is_array( $state ) )
                {
                    $value = (int) $state['id'];
                }
            } break;
        }
        return $value;
    }

    protected function generateContainsFilter( $field, $value, $negative )
    {
        if ( $negative ) $negative = '!';
        $fieldNames = $this->generateFieldName( $field );

        $filter = array();
        foreach( $fieldNames as $type => $fieldName )
        {
            if ( is_array( $value ) )
            {
                $data = array( 'and' );
                foreach ( $value as $item )
                {
                    $data[] = $negative . $fieldName . ':' . $this->formatFilterValue( $item, $type );
                }
            }
            else
            {
                $data = $negative . $fieldName . ':' . $this->formatFilterValue( $value, $type );
            }
            $filter[] = $data;
        }

        if ( count( $filter ) == 1 )
            $filter = array_pop( $filter );
        elseif ( count( $filter ) > 1 )
            array_unshift( $filter, 'or' );

        return $filter;
    }

    protected function generateInFilter( $field, $value, $negative )
    {
        if ( $negative ) $negative = '!';
        $fieldNames = $this->generateFieldName( $field );

        $filter = array();
        foreach( $fieldNames as $type => $fieldName )
        {
            if ( is_array( $value ) )
            {
                $data = array( 'or' );
                foreach ( $value as $item )
                {
                    $data[] = $negative . $fieldName . ':' . $this->formatFilterValue( $item, $type );
                }
            }
            else
            {
                $data = $negative . $fieldName . ':' . $this->formatFilterValue( $value, $type );
            }
            $filter[] = $data;
        }

        if ( count( $filter ) == 1 )
            $filter = array_pop( $filter );
        elseif ( count( $filter ) > 1 )
            array_unshift( $filter, 'or' );

        return $filter;
    }

    protected function generateRangeFilter( $field, $value, $negative )
    {
        if ( !is_array( $value ) )
            throw new Exception( "Range require an array value" );

        if ( $negative ) $negative = '!';
        $fieldNames = $this->generateFieldName( $field );

        $filter = array();
        foreach( $fieldNames as $type => $fieldName )
        {
            $filter[] = $negative . $fieldName . ':[' . $this->formatFilterValue( $value[0], $type ) . ' TO ' . $this->formatFilterValue( $value[1], $type ) . ']';
        }

        if ( count( $filter ) == 1 )
            $filter = array_pop( $filter );
        elseif ( count( $filter ) > 1 )
            array_unshift( $filter, 'or' );

        return $filter;
    }

    protected function generateEqFilter( $field, $value, $negative )
    {
        return $this->generateInFilter( $field, $value, $negative );
    }

    /**
     * Se è presente un parametro di classe restringe il campo degli attributi a disposizione
     */
    protected function filterAvailableFieldDefinitions()
    {
        if ( isset( $this->convertedQuery['SearchContentClassID'] ) )
        {
            $filteredAvailableFieldDefinitions = array();
            foreach ( $this->availableFieldDefinitions as $identifier => $fieldDefinition )
            {
                foreach ( $fieldDefinition as $dataType => $classes )
                {
                    $filteredClasses = array_intersect(
                        $this->convertedQuery['SearchContentClassID'],
                        $classes
                    );
                    if ( !empty( $filteredClasses ) )
                    {
                        $filteredAvailableFieldDefinitions[$identifier][$dataType] = $filteredClasses;
                    }
                }
            }
            $this->availableFieldDefinitions = $filteredAvailableFieldDefinitions;
        }
    }

    protected function generateFieldName( $field )
    {
        if ( in_array( $field, $this->metaFields ) )
        {
            if ( $field == 'section' )
            {
                $field = 'section_id';
            }
            elseif ( $field == 'state' )
            {
                $field = 'object_states';
            }
            return array( 'meta_' . $field => eZSolr::getMetaFieldName( $field, 'search' ) );
        }
        elseif ( isset( $this->availableFieldDefinitions[$field] ) )
        {
            return $this->getFieldName( $field, 'search' );
        }
        throw new Exception( "Can not convert field $field" );
    }

    /**
     * Mutuato da ezfSolrDocumentFieldBase restituisce il campo solr senza usare la classe eZContentClassAttribute
     *
     * @param $field
     * @param $context
     *
     * @return array
     */
    protected function getFieldName( $field, $context )
    {
        $data = array();
        if ( isset( $this->availableFieldDefinitions[$field] ) )
        {
            foreach ( $this->availableFieldDefinitions[$field] as $dataType => $classes )
            {
                $type = $this->getSolrType( $dataType, $context );
                if ( $dataType == 'ezobjectrelationlist' || $dataType == 'ezobjectrelation' )
                {
                    $data['sub_' . $type] = $this->generateSolrSubFieldName(
                        $field,
                        $type
                    );
                }
                else
                {
                    $data[$type] = $this->generateSolrFieldName(
                        $field,
                        $type
                    );
                }
            }
        }

        return $data;
    }

    /**
     * @see SentenceConverter::getFieldName
     *
     * @param $datatypeString
     * @param string $context
     *
     * @return string
     */
    protected function getSolrType( $datatypeString, $context = 'search' )
    {
        $eZFindINI = eZINI::instance( 'ezfind.ini' );
        $datatypeMapList = $eZFindINI->variable(
            'SolrFieldMapSettings',
            eZSolr::$fieldTypeContexts[$context]
        );
        if ( !empty( $datatypeMapList[$datatypeString] ) )
        {
            return $datatypeMapList[$datatypeString];
        }
        $datatypeMapList = $eZFindINI->variable( 'SolrFieldMapSettings', 'DatatypeMap' );
        if ( !empty( $datatypeMapList[$datatypeString] ) )
        {
            return $datatypeMapList[$datatypeString];
        }

        return $eZFindINI->variable( 'SolrFieldMapSettings', 'Default' );
    }

    /**
     * @see SentenceConverter::getFieldName
     *
     * @param $identifier
     * @param $type
     *
     * @return string
     */
    protected function generateSolrFieldName( $identifier, $type )
    {
        return $this->documentFieldName->lookupSchemaName(
            ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . $identifier,
            $type
        );
    }

    /**
     * @see SentenceConverter::getFieldName
     *
     * @param $identifier
     * @param $type
     * @param $subIdentifier
     *
     * @return string
     */
    protected function generateSolrSubFieldName( $identifier, $type, $subIdentifier = 'name' )
    {
        return $this->documentFieldName->lookupSchemaName(
            ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX . $identifier
            . ezfSolrDocumentFieldBase::SUBATTR_FIELD_SEPARATOR . $subIdentifier . ezfSolrDocumentFieldBase::SUBATTR_FIELD_SEPARATOR,
            $type
        );
    }

}