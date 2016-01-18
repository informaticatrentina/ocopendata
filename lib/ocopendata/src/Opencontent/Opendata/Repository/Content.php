<?php

namespace OpenContent\Opendata\Repository;

use OpenContent\Opendata\Values\ContentObject;

class Content
{
    public static function fetch( $contentObjectIdentifier )
    {
        return new ContentObject( array() );
    }
}