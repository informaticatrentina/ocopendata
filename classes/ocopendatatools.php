<?php
/**
 * Classe OCOpenDataTools
 * Tool per la gestione dei dataset e la loro esposizione
 * Estende la libreria Ckan_client
 */
class OCOpenDataTools extends Ckan_client
{
    /**
     * Separatore per il remote id
     */
    const SEPARATOR = ':';
    
    /**
     * Id di installazione
     * @see OCOpenDataTools::installationID()
     * @var string
     */
    static $InstallationID;
    
    /**
     * Risorse Api
     * @see parent::resources
     */
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
    
    /**
     * Configurazioni ini
     * @var eZINI
     */
    public $openDataIni;
    
    /**
     * @see parent::__construct
     * @var string $api_key
     */
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
    
    /**
     * Push di un oggetto (creazione o aggiornamento) in CKAN
     * @param eZContentObject|integer $object
     * @throws Exception
     */
    public function pushObject( $object )
    {
        if ( is_numeric( $object ) )
        {
            $object = eZContentObject::fetch( $object );
        }
        if ( $object instanceof eZContentObject )
        {
            try
            {
                $data = $this->getDatasetFromObject( $object );
                $postData = json_encode( $data );
                if ( isset( $data['id'] ) )
                {
                    $response = $this->post_package_update( $postData, $data['id'] );                
                }
                else
                {
                    $response = $this->post_package_register( $postData );                    
                    if ( isset( $response->id ) )
                    {
                        $object->setAttribute( 'remote_id', OCOpenDataConverter::$remotePrefix . $response->id );
                        $object->store();
                    }
                }
                return $response;
            }
            catch( Exception $e )
            {
                eZDebug::writeError( $e->getMessage() . ' on object id #' . $object->attribute( 'id' ), __METHOD__ );
                eZDebug::writeError( $data, __METHOD__ );
                throw new Exception( $e->getMessage() );
            }
        }
        else
        {
            throw new Exception( 'Object not found' );
        }
    }
    
    /**
     * Api di aggrionamento
     * @param array $data
     * @param string $packageId
     * @see parent::make_request
     */
    public function post_package_update( $data, $packageId )
	{
		return $this->make_request( 'POST', 
			$this->resources['package_register'] . '/' . $packageId, 
			$data );
	}
    
    /**
     * Costruisce il datatset a partire da un oggetto
     * @param eZContentObject $object
     * @throws Excepetion
     */
    public function getDatasetFromObject( eZContentObject $object )
    {
        if ( !$object instanceof eZContentObject )
        {
            throw new Exception( "Oggetto non trovato" );
        }
        if ( $object->attribute( 'class_identifier' ) == $this->openDataIni->variable( 'GeneralSettings', 'DatasetClassIdentifier' ) )
        {
            $resources = $this->parseResourcesFromObject( $object );
            $converter = new OCOpenDataConverter( $object, $resources );
            return $converter->getData();
        }
        throw new Exception( "L'oggetto {$object->attribute( 'id' )} non è di classe {$this->openDataIni->variable( 'GeneralSettings', 'DatasetClassIdentifier' )}" );
    }
    
    /**
     * Parse le risorse di un oggetto dataset
     * @param eZContentObject $object
     * @return array
     */
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
        foreach( $resources as $number => $resource )
        {
            if ( isset( $resource['url'] ) && $resource['url']->attribute( 'content' ) != '' )
            {
                unset( $resources[$number]['file'] );
                unset( $resources[$number]['api'] );
            }
            elseif ( isset( $resource['file'] ) && $resource['file']->attribute( 'content' ) != '' )
            {
                unset( $resources[$number]['url'] );
                unset( $resources[$number]['api'] );
            }
            elseif ( isset( $resource['api'] ) && $resource['api']->attribute( 'content' ) != '' )
            {
                unset( $resources[$number]['url'] );
                unset( $resources[$number]['file'] );
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
    
    /**
     * Genera id unico
     * @see self::installationID()
     * @param integer $objectId
     * @return string
     */
    public static function generateUniqueId( $objectId )
    {
        return self::installationID() . self::SEPARATOR . $objectId;
    }
    
    /**
     * Restituisce un oggetto, dato il suo id unico
     * @param string $id
     * @return eZContentObject or false
     */
    public function getObjectFromUniqueId( $id )
    {                
        $fromRemote = eZContentObject::fetchRemoteID( $id );
        if ( $fromRemote instanceof eZContentObject )
        {
            return $fromRemote;
        }
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
    
    /**
     * Restiuisce la lista delle classi filtrate sulla BlackList
     * @return eZContentClass[]
     */
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
    
    /**
     * Restituisce la classe filtrando su balck list
     * @param string $classIdentifier
     * @return eZContentClass or false
     */
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
        
    /**
     * Restituisce i nodi dataset nel sottoalbero dei contenuti
     * @return eZContentObjectTreeNode[]
     */
    public function getDatasetNodes()
    {
        $nodes = array();
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
            $nodes = eZContentObjectTreeNode::subTreeByNodeID( $params,
                                                               eZINI::instance( 'content.ini' )->variable( 'NodeSettings', 'RootNode' ) );            
        }
        return $nodes;
    }
    
    /**
     * Restituisce l'array degli id dataset nel sottoalbero dei contenuti
     * @return array
     */    
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
               $dataset[] = self::generateUniqueId( $node->attribute( 'contentobject_id' ) );
            }
        }
        return $dataset;
    }
    
    /**
     * Restituisce il dataset
     * @param string $datasetId
     * @return array
     */
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
    
    /**
     * Genera l'id di installazione
     * @see eZSolr::installationID()
     * @return string
     */
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
    
    /**
     * Restituice la lista dei datattype in black list
     * @return array
     */
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
    
    /**
     * Restituice la lista delle classi in black list
     * @return array
     */
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
    
    /**
     * Restituice la lista degli identificatori di attributo in black list
     * @return array
     */    
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
    
    /**
     * Restituice la lista degli ovveride degli identificatori di attributo 
     * @return array
     */
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
    
    /**
     * Restituice l'identificatore di attributo sulla base del loto override di identificatore
     * @param string $fieldName
     * @param string $classIdentifier
     * @return string
     */    
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
