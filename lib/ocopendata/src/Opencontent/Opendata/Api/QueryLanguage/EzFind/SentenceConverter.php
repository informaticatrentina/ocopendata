<?php

namespace Opencontent\Opendata\Api\QueryLanguage\EzFind;

use Opencontent\Opendata\Api\ClassRepository;
use Opencontent\Opendata\Api\StateRepository;
use Opencontent\Opendata\Api\SectionRepository;
use Opencontent\QueryLanguage\Parser\Sentence;
use Opencontent\QueryLanguage\Converter\Exception;
use ArrayObject;
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

    public function __construct( SolrNamesHelper $solrNamesHelper )
    {
        $this->solrNamesHelper = $solrNamesHelper;

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
        $field = $sentence->getField();
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
        return ( empty( $data ) ) ? null : $data;
    }

    protected function cleanValue( $value )
    {
        if ( is_array( $value ) )
        {
            $data = array();
            foreach( $value as $item )
            {
                $item = str_replace( "\'", "'", $item );
                $data[] = trim( $item, "'" );
            }
        }
        else
        {
            $value = str_replace( "\'", "'", $value );
            $data[] = trim( $value, "'" );
        }
        return $data;
    }

    protected function formatFilterValue( $value, $type )
    {
        $typeParts = explode( '.', $type );
        $type = array_pop( $typeParts );
        switch ( $type )
        {
            case 'meta_section';
            case 'meta_state';
            case 'meta_id';
            case 'tint':
            case 'sint':
            case 'int':
                $value = (int)$value;
                break;

            case 'float':
            case 'double':
            case 'sfloat':
            case 'tfloat':
                $value = (float)$value;
                break;

            case 'string':
                //$value = '((*' . strtolower( $value ) . '*) OR "' . $value . '"")';
                $value = '"' . $value . '"';
                break;

            case 'sub_string':
                $value = '"' . $value . '"';
                break;

            case 'meta_published':
            case 'meta_modified':
            case 'date':
            {
                if ( $value != '*' )
                {
                    $time = strtotime( $value );
                    if ( !$time )
                    {
                        throw new Exception( "Problem with date $value" );
                    }
                    $value = '"' . ezfSolrDocumentFieldBase::convertTimestampToDate( $time ) . '"';
                }
            }
                break;

            case 'meta_section_id':
            {
                $section = $this->sectionRepository->load( $value );
                if ( is_array( $section ) )
                {
                    $value = (int)$section['id'];
                }
            }
                break;

            case 'meta_object_states':
            {
                $state = $this->stateRepository->load( $value );
                if ( is_array( $state ) )
                {
                    $value = (int)$state['id'];
                }
            }
                break;
        }

        return $value;
    }

    protected function generateContainsFilter( $field, $value, $negative )
    {
        if ( $negative ) $negative = '!';
        $fieldNames = $this->solrNamesHelper->generateFieldNames( $field );

        $filter = array();
        foreach( $fieldNames as $type => $fieldName )
        {
            if ( is_array( $value ) )
            {
                if ( count( $value ) > 1 )
                {
                    $data = array( 'and' );
                    foreach ( $value as $item )
                    {
                        $data[] = $negative . $fieldName . ':' . $this->formatFilterValue( $item, $type );
                    }
                }
                else
                {
                    $data = $negative . $fieldName . ':' . $this->formatFilterValue( $value[0], $type );
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
        $fieldNames = $this->solrNamesHelper->generateFieldNames( $field );

        $filter = array();
        foreach( $fieldNames as $type => $fieldName )
        {
            if ( is_array( $value ) )
            {
                if ( count( $value ) > 1 )
                {
                    $data = array( 'or' );
                    foreach ( $value as $item )
                    {
                        $data[] = $negative . $fieldName . ':' . $this->formatFilterValue( $item, $type );
                    }
                }
                else
                {
                    $data = $negative . $fieldName . ':' . $this->formatFilterValue( $value[0], $type );
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
        $fieldNames = $this->solrNamesHelper->generateFieldNames( $field );

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

}