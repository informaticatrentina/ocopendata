<?php

namespace Opencontent\Opendata\Api\AttributeConverter;

use eZContentObjectAttribute;
use eZContentClassAttribute;
use eZUser;
use Opencontent\Opendata\Api\Exception\InvalidInputException;

class User extends Base
{
    public function getValue()
    {
        if ( $this->attribute->attribute( 'data_type_string' ) == 'ezuser'
             && $this->attribute instanceof eZContentObjectAttribute
             && $this->attribute->hasContent() )
        {
            /** @var eZUser $user */
            $user = $this->attribute->content();
            return array(
                'login' => $user->Login,
                'email' => $user->Email
            );
        }
        return null;
    }

    public function setValue( $data )
    {
        if ( !isset( $data['login'] ) || !isset( $data['email'] ) )
        {
            throw new InvalidInputException( 'Invalid input format', $this->getIdentifier(), $data );
        }
        if ( $this->attribute->attribute( 'data_type_string' ) == 'ezuser' )
        {
            /** @var eZUser $user */
            $user = eZUser::fetchByName( $data['login'] );
            if ( $user instanceof eZUser )
            {
                throw new InvalidInputException( 'Duplicate user login', $this->getIdentifier(), $data );
            }

            /** @var eZUser $user */
            $user = eZUser::fetchByEmail( $data['email'] );
            if ( $user instanceof eZUser )
            {
                throw new InvalidInputException( 'Duplicate user email', $this->getIdentifier(), $data );
            }

            return $data['login'] . '|' . $data['email'];
        }
        return null;
    }
}