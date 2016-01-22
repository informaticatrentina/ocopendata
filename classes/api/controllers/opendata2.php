<?php

use Opencontent\Opendata\Api\EnvironmentLoader;
use Opencontent\Opendata\Api\ContentBrowser;
use Opencontent\Opendata\Api\ContentRepository;
use Opencontent\Opendata\Api\ContentSearch;
use Opencontent\Opendata\Api\ClassRepository;
use Opencontent\Opendata\Api\Exception\BaseException;
use Opencontent\Opendata\Api\Values\ContentClass;

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
     * @var ClassRepository
     */
    protected $classRepository;

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
        $this->classRepository = new ClassRepository();
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
        $errorType = BaseException::cleanErrorCode( get_class( $exception ) );
        if ( $exception instanceof BaseException )
        {
            $serverErrorCode = $exception->getServerErrorCode();
            $errorType = $exception->getErrorType();
        }

        $result->status = new OcOpenDataErrorResponse(
            $serverErrorCode,
            $exception->getMessage(),
            $errorType
        );

        return $result;
    }

    public function doProtectedSearch()
    {
        return $this->doContentSearch();
    }

    public function doAnonymousSearch()
    {
        return $this->doContentSearch();
    }

    protected function doContentSearch()
    {
        try
        {
            $this->setEnvironment();
            $result = new ezpRestMvcResult();
            $search = $this->contentSearch->search( $this->request->variables['Query'] );
            $result->variables['query'] = $search->query;
            $result->variables['nextPage'] = $search->nextPageQuery;
            $result->variables['totalCount'] = $search->totalCount;
            $result->variables['searchHits'] = $search->searchHits;
        }
        catch ( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }

        return $result;
    }

    public function doProtectedBrowse()
    {
        return $this->doContentBrowse();
    }

    public function doAnonymousBrowse()
    {
        return $this->doContentBrowse();
    }

    protected function doContentBrowse()
    {
        $result = new ezpRestMvcResult();
        $browse = $this->contentBrowser->browse(
            $this->request->variables['ContentNodeIdentifier']
        );
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
        catch ( Exception $e )
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
            $content = $this->contentRepository->read(
                $this->request->variables['ContentObjectIdentifier']
            );
            $result->variables['metadata'] = $content->metadata;
            $result->variables['data'] = $content->data->jsonSerialize(
            ); // compat with php version<5.4
        }
        catch ( Exception $e )
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
        catch ( Exception $e )
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
        catch ( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }

        return $result;
    }

    public function doClassRead()
    {
        try
        {
            $result = new ezpRestMvcResult();
            $result->variables['class'] = $this->classRepository->load(
                $this->request->variables['Identifier']
            );
        }
        catch ( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }

        return $result;
    }

    public function doClassListRead()
    {
        try
        {
            $result = new ezpRestMvcResult();
            $list = $this->classRepository->listAll();
            $classes = array();
            $baseUri = $this->request->getBaseURI();
            $detailBaseUri = str_replace( 'classes', 'v2/classes', $baseUri ); //@todo
            $searchBaseUri = str_replace( 'classes', 'v2/content/search', $baseUri ); //@todo
            foreach ( $list as $item )
            {
                $item['link'] = $detailBaseUri . '/' . $item['identifier'];
                $item['search'] = ContentClass::isSearchable( $item['identifier'] ) ? $searchBaseUri . '/' . urlencode( 'classes ' . $item['identifier'] ) : null;
                $classes[] = $item;
            }
            $result->variables['classes'] = $classes;
        }
        catch ( Exception $e )
        {
            $result = $this->doExceptionResult( $e );
        }

        return $result;
    }
}