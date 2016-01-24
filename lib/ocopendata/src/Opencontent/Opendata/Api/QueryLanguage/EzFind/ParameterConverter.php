<?php

namespace Opencontent\Opendata\Api\QueryLanguage\EzFind;

use Opencontent\QueryLanguage\Converter\Exception;
use Opencontent\QueryLanguage\Parser\Parameter;
use Opencontent\QueryLanguage\Parser\Sentence;
use eZSolr;

class ParameterConverter extends SentenceConverter
{

    /**
     * @param Sentence $parameter
     * @return void
     * @throws Exception
     */
    public function convert( Sentence $parameter )
    {
        if ( $parameter instanceof Parameter )
        {
            $key = (string) $parameter->getKey();
            $value = $parameter->getValue();

            switch( $key )
            {
                case 'classes':
                    $this->convertClasses( $value );
                    $this->filterAvailableFieldDefinitions();
                    break;

                case 'sort':
                {
                    $this->convertSortBy( $value );
                } break;

                case 'limit':
                {
                    $this->convertLimit( $value );
                } break;

                case 'offset':
                {
                    $this->convertOffset( $value );
                } break;

                case 'subtree':
                {
                    $this->convertSubtree( $value );
                } break;

                default:
                    throw new Exception( "Can not convert $key parameter" );
            }
        }
    }

    protected function convertClasses( $value )
    {
        if ( !is_array( $value ) )
        {
            $value = array( $value );
        }
        $list = array();
        foreach( $value as $item )
            $list[] = trim( $item, "'" );
        $this->convertedQuery['SearchContentClassID'] = $list;
    }

    protected function convertSortBy( $value )
    {
        if ( is_array( $value ) )
        {
            $data = array();
            foreach( $value as $field => $order )
            {
                if ( !in_array( $order, array( 'asc', 'desc' ) ) )
                {
                    throw new Exception( "Can not convert sort order value: $order" );
                }
                $fieldName = $this->generateSortName( $field );
                if ( is_array( $fieldName ) )
                {
                    foreach( $fieldName as $name )
                    {
                        $data[$name] = $order;
                    }

                }
                else
                {
                    $data[$fieldName] = $order;
                }

            }
            $this->convertedQuery['SortBy'] = $data;
        }
        else
        {
            throw new Exception( "Sort parameter require an hash value" );
        }
    }

    protected function convertLimit( $value )
    {
        if ( is_array( $value ) )
        {
            throw new Exception( "Limit parameter require an integer value" );
        }
        $this->convertedQuery['SearchLimit'] = intval( $value );
    }

    protected function convertOffset( $value )
    {
        if ( is_array( $value ) )
        {
            throw new Exception( "Offset parameter require an integer value" );
        }
        $this->convertedQuery['SearchOffset'] = intval( $value );
    }

    protected function convertSubtree( $value )
    {
        if ( !is_array( $value ) )
        {
            $value = array( $value );
        }
        $value = array_map( 'intval', $value );
        $this->convertedQuery['SearchSubTreeArray'] = $value;
    }

    protected function generateSortName( $field )
    {
        if ( in_array( $field, $this->metaFields ) )
        {
            return eZSolr::getMetaFieldName( $field, 'sort' );
        }
        elseif ( isset( $this->availableFieldDefinitions[$field] ) )
        {
            return $this->getFieldName( $field, 'sort' );
        }
        throw new Exception( "Can not convert sort field $field" );
    }
}