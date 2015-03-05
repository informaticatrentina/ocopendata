<?php


class OCOpenDataProvider extends ezpRestApiProvider
{

    public function getRoutes()
    {
        $routes = array(
            'ezpListAtom'        => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/node/:nodeId/listAtom', 'ezpRestAtomController', 'collection' ), 1 ),
            // @TODO : Make possible to interchange optional params positions
            'ezpList'            => new ezpRestVersionedRoute( new ezpMvcRegexpRoute( '@^/content/node/(?P<nodeId>\d+)/list(?:/offset/(?P<offset>\d+))?(?:/limit/(?P<limit>\d+))?(?:/sort/(?P<sortKey>\w+)(?:/(?P<sortType>asc|desc))?)?$@', 'OCOpenDataController', 'list' ), 1 ),
            'ezpNode'            => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/node/:nodeId', 'OCOpenDataController', 'viewContent' ), 1 ),
            'ezpFieldsByNode'    => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/node/:nodeId/fields', 'OCOpenDataController', 'viewFields' ), 1 ),
            'ezpFieldByNode'     => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/node/:nodeId/field/:fieldIdentifier', 'OCOpenDataController', 'viewField' ), 1 ),
            'ezpChildrenCount'   => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/node/:nodeId/childrenCount', 'OCOpenDataController', 'countChildren' ), 1 ),
            'ezpObject'          => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/object/:objectId', 'OCOpenDataController', 'viewContent' ), 1 ),
            'ezpFieldsByObject'  => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/object/:objectId/fields', 'OCOpenDataController', 'viewFields' ), 1 ),
            'ezpFieldByObject'   => new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/object/:objectId/field/:fieldIdentifier', 'OCOpenDataController', 'viewField' ), 1 )
        );

        $routes['openDataListByClass'] = new ezpRestVersionedRoute(
            new ezpMvcRegexpRoute(
            //'@^/content/class/(?P<classIdentifier>\w+)(?:/offset/(?P<offset>\d+))?(?:/limit/(?P<limit>\d+))?(?:/sort/(?P<sortKey>\w+)(?:/(?P<sortType>asc|desc))?)?$@',
                '@^/content/class/(?P<classIdentifier>\w+)(?:/offset/(?P<offset>\d+))?(?:/limit/(?P<limit>\d+))?$@',
                'OCOpenDataController',
                'listByClass'
            ), 1 );

        $routes['openDataClassList'] = new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/classList', 'OCOpenDataController', 'listClasses' ), 1 );
        $routes['openDataInstantiatedClassList'] = new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/content/instantiatedClassList', 'OCOpenDataController', 'instantiatedListClasses' ), 1 );

        $routes['openDataHelp'] = new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/', 'OCOpenDataController', 'help' ), 1 );
        $routes['openDataHelpList'] = new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/help', 'OCOpenDataController', 'helpList' ), 1 );

        $routes['openDataDataset'] = new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/dataset', 'OCOpenDataController', 'datasetList' ), 1 );
        $routes['openDataDatasetView'] = new ezpRestVersionedRoute( new ezpMvcRailsRoute( '/dataset/:datasetId', 'OCOpenDataController', 'datasetView' ), 1 );

        return $routes;
    }

}
