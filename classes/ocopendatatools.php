<?php

class OCOpenDataTools extends Ckan_client
{
    const SEPARATOR = ':';
    
    static $InstallationID;
    
    protected $resources = array(
		'package_register' => 'rest/dataset',
		'package_entity' => 'rest/dataset',
		'group_register' => 'rest/group',
		'group_entity' => 'rest/group',
		'tag_register' => 'rest/tag',
		'tag_entity' => 'rest/tag',
		'rating_register' => 'rest/rating',
		'rating_entity' => 'rest/rating',
		'revision_register' => 'rest/revision',
		'revision_entity' => 'rest/revision',
		'license_list' => 'rest/licenses',
		'package_search' => 'search/dataset'
	);
    
    public $openDataIni;
    
    public function __construct( $api_key = false )
    {
        $this->openDataIni = eZINI::instance( 'ocopendata.ini' );
        $this->base_url = $this->openDataIni->variable( 'CkanSettings', 'BaseUrl' );
        $this->api_version = $this->openDataIni->variable( 'CkanSettings', 'ApiVersion' );
        if ( !$api_key && $this->openDataIni->hasVariable( 'CkanSettings', 'ApiKey' ) )
        {
            $api_key = $this->openDataIni->variable( 'CkanSettings', 'ApiKey' );
        }
        parent::__construct( $api_key );
    }
    
    public function post_package_update( $data, $packageId )
	{
		return $this->make_request( 'POST', 
			$this->resources['package_register'] . '/' . $packageId, 
			$data );
	}
    
    public function getDatasetFromObject( eZContentObject $object )
    {
        if ( $object->attribute( 'class_identifier' ) == $this->openDataIni->variable( 'GeneralSettings', 'DatasetClassIdentifier' ) )
        {
            $resources = $this->parseResourcesFromObject( $object );
            $converter = new OCOpenDataConverter( $object, $resources );
            return $converter->convert()->getData();
        }
        return array();
    }
    
    public function parseResourcesFromObject( eZContentObject $object )
    {
        $dataMap = $object->attribute( 'data_map' );
        $resources = array();
        foreach( array_keys( $dataMap ) as $attributeIdentifier )
        {
            if ( strpos( $attributeIdentifier, 'resource' ) !== false )
            {
                list( $resource, $number, $resourceIdentifier ) = explode( '_', $attributeIdentifier );
                $resources[intval( $number )][$resourceIdentifier] = $dataMap[$attributeIdentifier];
            }
        }
        
        $unset = array();
        foreach( $resources as $number => &$resource )
        {
            if ( isset( $resource['url'] ) && $resource['url']->attribute( 'has_content' ) )
            {
                unset( $resource['file'] );
                unset( $resource['api'] );
            }
            elseif ( isset( $resource['file'] ) && $resource['file']->attribute( 'has_content' ) )
            {
                unset( $resource['url'] );
                unset( $resource['api'] );
            }
            elseif ( isset( $resource['api'] ) && $resource['api']->attribute( 'has_content' ) )
            {
                unset( $resource['url'] );
                unset( $resource['file'] );
            }
            else
            {
                $unset[] = $number;
            }
        }
        foreach( $unset as $number )
        {
           unset( $resources[$number] );
        }
        return $resources;
    }
    
    public function generateUniqueId( $objectId )
    {
        return self::installationID() . self::SEPARATOR . $objectId;
    }
    
    public function getObjectFromUniqueId( $id )
    {                
        $parts = explode( self::SEPARATOR, $id );        
        if ( $parts[0] == self::installationID() )
        {
            $object = eZContentObject::fetch( $parts[1] );            
            if ( $object instanceof eZContentObject )
            {
                if ( $object->attribute( 'class_identifier' ) == $this->openDataIni->variable( 'GeneralSettings', 'DatasetClassIdentifier' ) )
                {
                    return $object;
                }
            }
        }
        return false;
    }
    
    public function getClassList()
    {
        $return = array();
        $classes = eZContentClass::fetchList();
        $classBlacklist = self::getClassBlacklist();
        foreach( $classes as $class )
        {
            if ( !isset( $classBlacklist[$class->attribute('identifier')] ) )
            {
                $return[$class->attribute('identifier')] = $class;
            }
        }
        ksort( $return );
        return $return;
    }
    
    public function getClass( $classIdentifier )
    {
        $classBlacklist = self::getClassBlacklist();
        if ( isset( $classBlacklist[$classIdentifier] ) )
        {
            return false;
        }
        $class = eZContentClass::fetchByIdentifier( $classIdentifier );        
        return $class;
    }
        
    
    public function getDatasetIdArray()
    {
        $dataset = array();
        $classIdentifier = $this->openDataIni->variable( 'GeneralSettings', 'DatasetClassIdentifier' );
        $class = eZContentClass::fetchByIdentifier( $classIdentifier );
        if ( $class instanceof eZContentClass )
        {
            $params = array(
                'ClassFilterType' => 'include',
                'ClassFilterArray' => array( $classIdentifier ),
                'Depth' => 1,
                'DepthOperator' => 'ge'
            );
            $nodes = eZContentObjectTreeNode::subTreeByNodeID( $params, eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );
            foreach( $nodes as $node )
            {
               $dataset[] = $this->generateUniqueId( $node->attribute( 'contentobject_id' ) );
            }
        }
        return $dataset;
    }
    
    public function getDataset( $datasetId )
    {        
        $dataset = array();
        $object = $this->getObjectFromUniqueId( $datasetId );
        if ( $object )
        {
            $dataset = $this->getDatasetFromObject( $object );
        }
        return $dataset;
    }
    
    public static function installationID()
    {
        if ( class_exists( 'eZSolr' ) && method_exists( 'eZSolr', 'installationID' ) )
        {
            return eZSolr::installationID();
        }
        
        if ( !empty( self::$InstallationID ) )
        {
            return self::$InstallationID;
        }
        $db = eZDB::instance();

        $resultSet = $db->arrayQuery( 'SELECT value FROM ezsite_data WHERE name=\'ezfind_site_id\'' );

        if ( count( $resultSet ) >= 1 )
        {
            self::$InstallationID = $resultSet[0]['value'];
        }
        else
        {
            self::$InstallationID = md5( time() . '-' . mt_rand() );
            $db->query( 'INSERT INTO ezsite_data ( name, value ) values( \'ezfind_site_id\', \'' . self::$InstallationID . '\' )' );
        }

        return self::$InstallationID;
    }
    
    public static function getDatatypeBlackList()
    {
        $datatypeBlacklist = array();
        if ( eZINI::instance( 'ocopendata.ini' )->hasVariable( 'ContentSettings', 'DatatypeBlackListForExternal' ) )
        {
            $datatypeBlacklist = array_fill_keys(
                eZINI::instance( 'ocopendata.ini' )->variable( 'ContentSettings', 'DatatypeBlackListForExternal' ),
                true
            );
        }
        return $datatypeBlacklist;
    }
    
    public static function getClassBlacklist()
    {
        $classBlacklist = array();
        if ( eZINI::instance( 'ocopendata.ini' )->hasVariable( 'ContentSettings', 'ClassIdentifierBlackListForExternal' ) )
        {
            $classBlacklist = array_fill_keys(
                eZINI::instance( 'ocopendata.ini' )->variable( 'ContentSettings', 'ClassIdentifierBlackListForExternal' ),
                true
            );
        }
        return $classBlacklist;
    }
    
    public static function getFieldBlacklist()
    {
        $fieldBlacklist = array();
        if ( eZINI::instance( 'ocopendata.ini' )->hasVariable( 'ContentSettings', 'IdentifierBlackListForExternal' ) )
        {
            $fieldBlacklist = array_fill_keys(
                eZINI::instance( 'ocopendata.ini' )->variable( 'ContentSettings', 'IdentifierBlackListForExternal' ),
                true
            );
        }
        return $fieldBlacklist;
    }
    
    public static function getOverrideFieldIdentifier( $fieldName, $classIdentifier )
    {
        $list = array();
        if ( eZINI::instance( 'ocopendata.ini' )->hasVariable( 'ContentSettings', 'OverrideFieldIdentifierList' ) )
        {
            $list = eZINI::instance( 'ocopendata.ini' )->variableArray( 'ContentSettings', 'OverrideFieldIdentifierList' );
            foreach( $list as $nameArray )
            {
                if ( ( $nameArray[0] == $fieldName || $nameArray[0] == $classIdentifier . '/' . $fieldName )
                     && isset( $nameArray[1] ) )
                {
                    return $nameArray[1];                    
                }
            }
        }
        return $fieldName;
    }
    
    public static function getRealFieldIdentifier( $fieldName, $classIdentifier )
    {
        $list = array();
        if ( eZINI::instance( 'ocopendata.ini' )->hasVariable( 'ContentSettings', 'OverrideFieldIdentifierList' ) )
        {
            $list = eZINI::instance( 'ocopendata.ini' )->variableArray( 'ContentSettings', 'OverrideFieldIdentifierList' );
            foreach( $list as $nameArray )
            {
                if ( isset( $nameArray[1] ) && $nameArray[1] == $fieldName )
                {
                    $realField = explode( '/', $nameArray[0] );
                    if ( count( $realField ) == 1 )
                    {
                        return $realField;
                    }
                    elseif ( count( $realField ) == 2 )
                    {
                        if ( $realField[0] == $classIdentifier )
                        {
                            return $realField[1];
                        }
                    }                    
                }
            }
        }
        return $fieldName;
    }
    
}
