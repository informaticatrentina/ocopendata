<?php

namespace OpenContent\Opendata\Rest\Server\MVC\ezc;

use ezpRestContentController;
use ezpRestMvcResult;
use OpenContent\Opendata\Repository\Content;

class Controller extends ezpRestContentController
{
    public function doContentSearch()
    {
        $result = new ezpRestMvcResult();
        return $result;
    }

    public function doContentBrowse()
    {
        $result = new ezpRestMvcResult();
        return $result;
    }

    public function doContentCreate()
    {
        $result = new ezpRestMvcResult();
        return $result;
    }

    public function doContentRead()
    {
        $result = new ezpRestMvcResult();
        $repository = new Content();
        $result->variables = $repository->fetch( $this->request->ContentObjectIdentifier );
        return $result;
    }

    public function doContentUpdate()
    {
        $result = new ezpRestMvcResult();
        return $result;
    }

    public function doContentDelete()
    {
        $result = new ezpRestMvcResult();
        return $result;
    }

}