<?php

namespace Opencontent\QueryLanguage\Parser;

class Sentence
{
    /**
     * @var Token
     */
    protected $field;

    /**
     * @var Token[]
     */
    protected $operator = array();

    /**
     * @var Token[]
     */
    protected $value = array();

    public function getField()
    {
        return $this->field;
    }

    public function getOperator()
    {
        return implode( ' ', $this->operator );
    }

    public function getValue()
    {
        return $this->normalizeValue();
    }

    public function setField( Token $data )
    {
        $this->field = $data;
    }

    public function setOperator( Token $data )
    {
        $this->operator[] = $data;
    }

    public function setValue( Token $data )
    {
        $this->value[] = $data;
    }

    public function __toString()
    {
        return $this->getField() . ' ' . $this->getOperator() . ' ' . $this->stringValue();
    }

    public function isValid()
    {
        return $this->field !== null && !empty( $this->operator ) && !empty( $this->value );
    }

    public function stringValue()
    {
        $value = $this->getValue();
        if ( is_array( $value ) )
        {
            if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) )
            {
                $string = '[' . implode( ',', $value ) . ']';
            }
            else
            {
                $valueArray = array();
                foreach( $value as $key => $item )
                {
                    $valueArray[] = $key . '=>' . $item;
                }
                $string = '[' . implode( ',', $valueArray ) . ']';
            }
        }
        else
        {
            $string = (string)$value;
        }

        return $string;
    }

    protected function normalizeValue()
    {
        if ( is_array( $this->value ) )
        {
            $value = implode( ' ', $this->value );
        }
        else
        {
            $value = (string)$this->value;
        }

        return self::parseString( $value );
    }

    public static function parseString( $variableValue )
    {
        if ( strpos( $variableValue, '[' ) === 0 )
        {
            $variableValue = str_replace( '[', '', $variableValue );
            $variableValue = str_replace( ']', '', $variableValue );
            if ( $variableValue == '' )
            {
                return array();
            }
            else
            {
                $variableValue = explode( ',', $variableValue );
                $variableValue = array_map( 'trim', $variableValue );
                foreach ( $variableValue as $value )
                {
                    if ( strpos( $value, '=>' ) !== false )
                    {
                        return self::parseHash( $variableValue );
                    }
                }
            }
        }

        return $variableValue;
    }

    protected static function parseHash( $array )
    {
        $variableValue = array();
        foreach ( $array as $item )
        {
            @list( $key, $value ) = explode( '=>', $item );
            $variableValue[trim( $key )] = trim( $value );
        }

        return $variableValue;
    }
}