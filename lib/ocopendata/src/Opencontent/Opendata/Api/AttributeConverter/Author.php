<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZMail;
use Opencontent\Opendata\Api\Exception\InvalidInputException;


class Author extends Base
{
    public function get( eZContentObjectAttribute $attribute )
    {
        $content = parent::get( $attribute );
        $authorList = array();
        foreach ( $attribute->attribute( 'content' )->attribute( 'author_list' ) as $author )
        {
            $authorList[] = array(
                'name' => $author['name'],
                'email' => $author['email'],
            );
        }
        $content['content'] = $authorList;
        return $content;
    }

    public function set( $data )
    {
        $stringItems = array();
        foreach( $data as $author )
        {
            $stringItems[] = implode( '|', $author );
        }
        return implode( '&', $stringItems );
    }

    public static function validate( $identifier, $data )
    {
        if ( !is_array( $data ) )
        {
            throw new InvalidInputException( 'Invalid type', $identifier, $data );
        }

        foreach( $data as $item )
        {
            if ( !isset( $item['name'] ) || !isset( $item['email'] ) )
            {
                throw new InvalidInputException( 'Invalid type', $identifier, $data );
            }
            if ( !eZMail::validate( $data['email'] ) )
            {
                throw new InvalidInputException( 'Invalid email', $identifier, $data );
            }
        }
    }

    public function type()
    {
        return array(
            'identifier' => 'authorList',
            'format' => array(
                array(
                    'name' => 'string',
                    'email' => 'string'
                )
            )
        );
    }
}