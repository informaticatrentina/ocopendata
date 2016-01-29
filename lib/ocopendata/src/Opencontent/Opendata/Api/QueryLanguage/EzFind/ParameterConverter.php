<?php

namespace Opencontent\Opendata\Api\QueryLanguage\EzFind;

use Opencontent\QueryLanguage\Converter\Exception;
use Opencontent\QueryLanguage\Parser\Parameter;
use Opencontent\QueryLanguage\Parser\Sentence;

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

        foreach( $list as $class )
        {
            if ( !in_array( $class, $this->classRepository->listClassIdentifiers() ) )
                throw new Exception( "Class $class not found" );
        }
        $this->solrNamesHelper->filterAvailableFieldDefinitionsByClasses( $list );
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
                $fieldNames = $this->solrNamesHelper->generateSortNames( $field  );
                foreach( $fieldNames as $name )
                {
                    $data[$name] = $order;
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
}