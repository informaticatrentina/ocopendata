<?php

use OpenContent\Opendata\Rest\Server\MVC\ezc\Route;
use OpenContent\Opendata\Rest\Server\MVC\ezc\Controller;

class OCOpenDataProvider extends ezpRestApiProvider
{

    public function getRoutes()
    {
        return array_merge(
            $this->getVersion1Routes(),
            $this->getVersion2Routes()
        );
    }

    public function getVersion2Routes()
    {
        $routes = array(
            'search' => new Route(
                new ezpMvcRailsRoute(
                    '/content/search/:Query/:Page',
                    'OpenContent\Opendata\Rest\Server\MVC\ezc\Controller',
                    'contentSearch',
                    array(),
                    'http-get'
                ), 2
            ),
            'browse' => new Route(
                new ezpMvcRailsRoute(
                    '/content/browse/:NodeId',
                    'OpenContent\Opendata\Rest\Server\MVC\ezc\Controller',
                    'contentBrowse',
                    array(),
                    'http-get'
                ), 2
            ),
            'create' => new Route(
                new ezpMvcRailsRoute(
                    '/content/create',
                    'OpenContent\Opendata\Rest\Server\MVC\ezc\Controller',
                    'contentCreate',
                    array(),
                    'http-post'
                ), 2
            ),
            'read' => new Route(
                new ezpMvcRailsRoute(
                    '/content/read/:ContentObjectIdentifier',
                    'OpenContent\Opendata\Rest\Server\MVC\ezc\Controller',
                    'contentRead',
                    array(),
                    'http-get'
                ), 2
            ),
            'update' => new Route(
                new ezpMvcRailsRoute(
                    '/content/update',
                    'OpenContent\Opendata\Rest\Server\MVC\ezc\Controller',
                    'contentUpdate',
                    array(),
                    'http-post'
                ), 2
            ),
            'delete' => new Route(
                new ezpMvcRailsRoute(
                    '/content/delete',
                    'OpenContent\Opendata\Rest\Server\MVC\ezc\Controller',
                    'contentDelete',
                    array(),
                    'http-post'
                ), 2
            )

        );

        return $routes;
    }

    public function getVersion1Routes()
    {
        $routes = array(
            'ezpListAtom' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/node/:nodeId/listAtom',
                    'ezpRestAtomController',
                    'collection'
                ), 1
            ),
            // @TODO : Make possible to interchange optional params positions
            'ezpList' => new ezpRestVersionedRoute(
                new ezpMvcRegexpRoute(
                    '@^/content/node/(?P<nodeId>\d+)/list(?:/offset/(?P<offset>\d+))?(?:/limit/(?P<limit>\d+))?(?:/sort/(?P<sortKey>\w+)(?:/(?P<sortType>asc|desc))?)?$@',
                    'OCOpenDataController',
                    'list'
                ), 1
            ),
            'ezpNode' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/node/:nodeId', 'OCOpenDataController', 'viewContent'
                ), 1
            ),
            'ezpFieldsByNode' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/node/:nodeId/fields',
                    'OCOpenDataController',
                    'viewFields'
                ), 1
            ),
            'ezpFieldByNode' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/node/:nodeId/field/:fieldIdentifier',
                    'OCOpenDataController',
                    'viewField'
                ), 1
            ),
            'ezpChildrenCount' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/node/:nodeId/childrenCount',
                    'OCOpenDataController',
                    'countChildren'
                ), 1
            ),
            'ezpObject' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/object/:objectId',
                    'OCOpenDataController',
                    'viewContent'
                ), 1
            ),
            'ezpFieldsByObject' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/object/:objectId/fields',
                    'OCOpenDataController',
                    'viewFields'
                ), 1
            ),
            'ezpFieldByObject' => new ezpRestVersionedRoute(
                new ezpMvcRailsRoute(
                    '/content/object/:objectId/field/:fieldIdentifier',
                    'OCOpenDataController',
                    'viewField'
                ), 1
            )
        );

        $routes['openDataListByClass'] = new ezpRestVersionedRoute(
            new ezpMvcRegexpRoute(
            //'@^/content/class/(?P<classIdentifier>\w+)(?:/offset/(?P<offset>\d+))?(?:/limit/(?P<limit>\d+))?(?:/sort/(?P<sortKey>\w+)(?:/(?P<sortType>asc|desc))?)?$@',
                '@^/content/class/(?P<classIdentifier>\w+)(?:/offset/(?P<offset>\d+))?(?:/limit/(?P<limit>\d+))?$@',
                'OCOpenDataController',
                'listByClass'
            ), 1
        );

        $routes['openDataClassList'] = new ezpRestVersionedRoute(
            new ezpMvcRailsRoute( '/content/classList', 'OCOpenDataController', 'listClasses' ), 1
        );
        $routes['openDataInstantiatedClassList'] = new ezpRestVersionedRoute(
            new ezpMvcRailsRoute(
                '/content/instantiatedClassList',
                'OCOpenDataController',
                'instantiatedListClasses'
            ), 1
        );

        $routes['openDataHelp'] = new ezpRestVersionedRoute(
            new ezpMvcRailsRoute( '/', 'OCOpenDataController', 'help' ), 1
        );
        $routes['openDataHelpList'] = new ezpRestVersionedRoute(
            new ezpMvcRailsRoute( '/help', 'OCOpenDataController', 'helpList' ), 1
        );

        $routes['openDataDataset'] = new ezpRestVersionedRoute(
            new ezpMvcRailsRoute( '/dataset', 'OCOpenDataController', 'datasetList' ), 1
        );
        $routes['openDataDatasetView'] = new ezpRestVersionedRoute(
            new ezpMvcRailsRoute( '/dataset/:datasetId', 'OCOpenDataController', 'datasetView' ), 1
        );

        return $routes;
    }

}
