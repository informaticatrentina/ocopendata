<?php


namespace Opencontent\Opendata\Api\QueryLanguage\EzFind;

use eZINI;
use eZSolr;
use ezfSolrDocumentFieldBase;
use ezfSolrDocumentFieldName;
use Opencontent\QueryLanguage\Converter\Exception;
use Opencontent\QueryLanguage\Parser\Token;


class SolrNamesHelper
{
    /**
     * @var ezfSolrDocumentFieldName
     */
    protected $documentFieldName;

    /**
     * @var \ArrayObject
     */
    protected $availableFieldDefinitions;

    protected $originalAvailableFieldDefinitions;

    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    public function __construct( array $availableFieldDefinitions, TokenFactory $tokenFactory )
    {
        $this->documentFieldName = new ezfSolrDocumentFieldName();
        $this->originalAvailableFieldDefinitions = $availableFieldDefinitions;
        $this->availableFieldDefinitions = new \ArrayObject( $availableFieldDefinitions );
        $this->tokenFactory = $tokenFactory;
    }

    /**
     * Se è presente un parametro di classe restringe il campo degli attributi a disposizione
     * @param $classList
     */
    public function filterAvailableFieldDefinitionsByClasses( $classList )
    {

        $filteredAvailableFieldDefinitions = array();
        foreach ( $this->availableFieldDefinitions as $identifier => $fieldDefinition )
        {
            foreach ( $fieldDefinition as $dataType => $classes )
            {
                $filteredClasses = array_intersect(
                    $classList,
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

    public function generateSortNames( $field )
    {
        return $this->generateFieldNames( $field, 'sort' );
    }

    public function generateFieldNames( $field, $context = 'search'  )
    {
        if ( !$field instanceof Token )
        {
            $field = $this->tokenFactory->createQueryToken( $field );
        }

        if ( $field->data( 'is_meta_field' ) )
        {
            if ( $field == 'section' )
            {
                $field = 'section_id';
            }
            elseif ( $field == 'state' )
            {
                $field = 'object_states';
            }
            return array( 'meta_' . $field => $this->getMetaFieldName( (string)$field, $context ) );
        }
        elseif ( $field->data( 'is_field' ) )
        {

            if ( $subFields = $field->data( 'sub_fields' ) )
            {
                if ( count( $subFields ) == 2 )
                {
                    $mainField = $subFields[0];
                    $subField = $subFields[1];
                    return $this->getSubFieldNames( $mainField, $subField, $context );
                }
                else
                {
                    throw new Exception( "Max one subfield is allowed ($field)" );
                }
            }

            return $this->getFieldNames( $field, $context );
        }
        throw new Exception( "Can not convert field $field" );
    }

    protected function getMetaFieldName( $field, $context )
    {
        return eZSolr::getMetaFieldName( (string)$field, $context );
    }

    protected function getFieldNames( Token $field, $context )
    {
        $data = array();

        $dataTypes = $this->getDatatypesByIdentifier( (string)$field );
        foreach( $dataTypes as $dataType )
        {
            if ( $dataType == 'ezobjectrelationlist' || $dataType == 'ezobjectrelation' )
            {
                $subField = $this->tokenFactory->createQueryToken( 'name' );
                $subField->data( 'is_meta_field', true );
                $data = array_merge( $data, $this->getSubFieldNames( $field, $subField, $context ) );
            }
            else
            {
                $type = $this->getSolrType( $dataType, $context );
                $data[$field. '.' . $type] = $this->generateSolrFieldName(
                    (string)$field,
                    $type
                );
            }
        }

        if ( empty( $data ) )
            throw new Exception( "{$field} not found or not searchable" );
        return $data;
    }

    protected function getSubFieldNames( Token $field, Token $subField, $context )
    {
        $data = array();
        $dataTypes = $this->getDatatypesByIdentifier( (string)$field );
        foreach( $dataTypes as $dataType )
        {
            if ( $subField && ( $dataType == 'ezobjectrelationlist' || $dataType == 'ezobjectrelation' ) )
            {
                if ( $subField->data( 'is_meta_field' ) )
                {
                    $data[$field . '.meta_' . $subField] = $this->generateSolrSubMetaFieldName(
                        (string)$field,
                        (string)$subField
                    );
                }
                elseif ( $subField->data( 'is_field' ) )
                {
                    $subDataTypes = $this->getUnFilteredDatatypesByIdentifier( (string)$subField );
                    foreach ( $subDataTypes as $subDataType )
                    {
                        $type = $this->getSolrType( $subDataType, $context );
                        $data[$field . '/' . $subField . '.' . $type] = $this->generateSolrSubFieldName(
                            (string)$field,
                            (string)$subField,
                            $type
                        );
                    }
                }
            }
            else
            {
                throw new Exception( "Field $subField not allowed as sub field of $field" );
            }
        }
        if ( empty( $data ) )
            throw new Exception( "{$field}.{$subField} not found or not searchable" );
        return $data;
    }

    public function getDatatypesByIdentifier( $identifier )
    {
        if ( isset( $this->availableFieldDefinitions[$identifier] ) )
        {
            return array_keys( $this->availableFieldDefinitions[$identifier] );
        }
        throw new Exception( "Field $identifier not found or not searchable in query class range" );
    }

    public function getUnFilteredDatatypesByIdentifier( $identifier )
    {
        if ( isset( $this->originalAvailableFieldDefinitions[$identifier] ) )
        {
            return array_keys( $this->originalAvailableFieldDefinitions[$identifier] );
        }
        throw new Exception( "Field $identifier not found or not searchable" );
    }

    public function getIdentifiersByDatatype( $datatype )
    {
        $result = array();
        foreach ( $this->availableFieldDefinitions as $identifier => $data )
        {
            if ( array_key_exists( $datatype, $data ) )
            {
                $result[] = $identifier;
            }
        }
        if ( !empty( $result ) )
            return $result;
        throw new Exception( "Datatype $datatype not found or not searchable in query class range" );
    }

    public function getUnFilteredIdentifiersByDatatype( $datatype )
    {
        $result = array();
        foreach ( $this->originalAvailableFieldDefinitions as $identifier => $data )
        {
            if ( array_key_exists( $datatype, $data ) )
            {
                $result[] = $identifier;
            }
        }
        if ( !empty( $result ) )
            return $result;
        throw new Exception( "Datatype $datatype not found or not searchable" );
    }

    /**
     * @see SentenceConverter::getFieldName
     *
     * @param $datatypeString
     * @param string $context
     *
     * @return string
     */
    public function getSolrType( $datatypeString, $context = 'search' )
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
    public function generateSolrFieldName( $identifier, $type )
    {
        return $this->documentFieldName->lookupSchemaName(
            ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX
            . $identifier,
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
    public function generateSolrSubFieldName( $identifier, $subIdentifier, $type )
    {
        return $this->documentFieldName->lookupSchemaName(
            ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX
            . $identifier
            . ezfSolrDocumentFieldBase::SUBATTR_FIELD_SEPARATOR
            . $subIdentifier . ezfSolrDocumentFieldBase::SUBATTR_FIELD_SEPARATOR,
            $type
        );
    }

    public function generateSolrSubMetaFieldName( $identifier, $subIdentifier )
    {
        // per via del ocsolrdocumentfieldobjectrelation.php di ocsearchtools
        if ( $subIdentifier == 'name' )
        {
            return $this->documentFieldName->lookupSchemaName(
                ezfSolrDocumentFieldBase::SUBATTR_FIELD_PREFIX
                . $identifier
                . ezfSolrDocumentFieldBase::SUBATTR_FIELD_SEPARATOR
                . $subIdentifier
                . ezfSolrDocumentFieldBase::SUBATTR_FIELD_SEPARATOR,
                'string'
            );
        }

        if ( $subIdentifier == 'section' )
        {
            $subIdentifier = 'section_id';
        }
        elseif ( $subIdentifier == 'state' )
        {
            $subIdentifier = 'object_states';
        }

        return $this->documentFieldName->lookupSchemaName(
            ezfSolrDocumentFieldBase::SUBMETA_FIELD_PREFIX
            . $identifier
            . ezfSolrDocumentFieldBase::SUBATTR_FIELD_SEPARATOR
            . $subIdentifier,
            eZSolr::getMetaAttributeType( $subIdentifier )
        );
    }
}