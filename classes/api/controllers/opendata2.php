<?php

use Opencontent\Opendata\Api\EnvironmentLoader;
use Opencontent\Opendata\Api\ContentBrowser;
use Opencontent\Opendata\Api\ContentRepository;
use Opencontent\Opendata\Api\ContentSearch;
use Opencontent\Opendata\Api\Exception\BaseException;

class OCOpenDataController2 extends ezpRestContentController
{
    /**
     * @var ContentRepository;
     */
    protected $contentRepository;

    /**
     * @var ContentBrowser;
     */
    protected $contentBrowser;

    /**
     * @var ContentSearch
     */
    protected $contentSearch;

    /**
     * @var ezpRestRequest
     */
    protected $request;

    public function __construct( $action, ezcMvcRequest $request )
    {
        parent::__construct( $action, $request );
        $this->contentRepository = new ContentRepository();
        $this->contentBrowser = new ContentBrowser();
        $this->contentSearch = new ContentSearch();
    }

    protected function setEnvironment()
    {
        $environmentIdentifier = $this->request->variables['EnvironmentSettigs'];
        $currentEnvironment = EnvironmentLoader::loadPreset( $environmentIdentifier );
        $this->contentRepository->setEnvironment( $currentEnvironment );
        $this->contentBrowser->setEnvironment( $currentEnvironment );
        $this->contentSearch->setEnvironment( $currentEnvironment );
    }

    protected function getPayload()
    {
        $data = json_decode( file_get_contents( "php://input" ), true );
        return $data;
    }

    protected function doExceptionResult( Exception $exception )
    {
        $result = new ezcMvcResult;
        $result->variables['message'] = $exception->getMessage();

        $serverErrorCode = ezpHttpResponseCodes::SERVER_ERROR;
        if ( $exception instanceof BaseException )
        {
            $serverErrorCode = $exception->getServerErrorCode();
        }

        $result->status = new OcOpenDataErrorResponse(
            $serverErrorCode,
            $exception->getMessage(),
            $exception->getCode()
        );

        return $result;
    }

    public function doContentSearch()
    {
        $result = new ezpRestMvcResult();
        $search = $this->contentSearch->search( $this->request->variables['Query'] );
        $result->variables['total'] = $search->count;
        $result->variables['contents'] = $search->contents;
        $result->variables['nextPage'] = $search->nextPage;
        $result->variables['query'] = $search->query;
        return $result;
    }

    public function doContentBrowse()
    {
        $result = new ezpRestMvcResult();
        $browse = $this->contentBrowser->browse( $this->request->variables['ContentNodeIdentifier'] );
        $result->variables['current'] = $browse->current;
        $result->variables['children'] = $browse->children;
        $result->variables['parent'] = $browse->parent;
        return $result;
    }

    public function doContentCreate()
    {
        try
        {
            $this->setEnvironment();
            $result = new ezpRestMvcResult();
            $result->variables['result'] = $this->contentRepository->create( $this->getPayload() );
        }
        catch( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }
        return $result;
    }

    public function doProtectedRead()
    {
        return $this->doContentRead();
    }

    public function doAnonymousRead()
    {
        return $this->doContentRead();
    }

    protected function doContentRead()
    {
        try
        {
            $this->setEnvironment();
            $result = new ezpRestMvcResult();
            $content = $this->contentRepository->read( $this->request->variables['ContentObjectIdentifier'] );
            $result->variables['metadata'] = $content->metadata;
            $result->variables['data'] = $content->data->jsonSerialize(); // compat with php version<5.4
        }
        catch( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }
        return $result;
    }

    public function doContentUpdate()
    {
        try
        {
            $this->setEnvironment();
            $result = new ezpRestMvcResult();
            $result->variables['result'] = $this->contentRepository->update( $this->getPayload() );
        }
        catch( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }
        return $result;
    }

    public function doContentDelete()
    {
        try
        {
            $this->setEnvironment();
            $result = new ezpRestMvcResult();
            $result->variables['result'] = $this->contentRepository->delete( $this->getPayload() );
        }
        catch( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }
        return $result;
    }
}