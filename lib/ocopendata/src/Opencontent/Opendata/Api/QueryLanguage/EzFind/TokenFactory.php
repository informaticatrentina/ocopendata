<?php

namespace Opencontent\Opendata\Api\QueryLanguage\EzFind;

use Opencontent\QueryLanguage\Parser\Token;
use Opencontent\QueryLanguage\Parser\TokenFactory as BaseTokenFactory;

class TokenFactory extends BaseTokenFactory
{
    public $functionFields = array();

    public $metaFields = array();

    public function __construct( $fields, $metaFields, $functionFields, $operators, $parameters, $clauses )
    {
        $this->fields = $fields;
        $this->metaFields = $metaFields;
        $this->functionFields = $functionFields;
        $this->operators = $operators;
        $this->parameters = $parameters;
        $this->clauses = $clauses;

        $fields = array_merge(
            $fields,
            $metaFields
        );
    }

    protected function isField( Token $token )
    {
        return $this->findFieldType( $token );
    }

    protected function findFieldType( Token $token )
    {
        $string = (string)$token;
        $subParts = explode( '.', $string );
        if ( count( $subParts ) > 1 )
        {
            $subTokens = array();
            foreach( $subParts as $part )
            {
                $tokenPart = $this->createQueryToken( $part );
                if ( !$this->isField( $tokenPart ) )
                    return false;
                else
                    $subTokens[] = $tokenPart;
            }
            $token->data( 'is_field', true );
            $token->data( 'sub_fields', $subTokens );
            return true;
        }

        if( in_array( $string, $this->metaFields ) )
        {
            $token->data( 'is_meta_field', true );
            return true;
        }
        elseif( in_array( $string, $this->fields ) )
        {
            $token->data( 'is_field', true );
            return true;
        }
        else
        {
            foreach( $this->functionFields as $functionField )
            {
                if ( strpos( $string, $functionField ) === 0 )
                {
                    $token->data( 'is_function_field', true );
                    return true;
                }
            }
        }

        return false;
    }
}