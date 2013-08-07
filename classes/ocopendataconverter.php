<?php

class OCOpenDataConverter
{
    private $data = array();
    
    protected $object;
    protected $dataMap;
    protected $resources;
    
    public $datasetMetaAttributes = array(
        "id",
        "metadata_created",
        "metadata_modified",
        "name",
        "url",
        "version"
    );
    
    public $datasetAttributes = array(            
        "title",        
        "author",
        "author_email",
        "maintainer",
        "maintainer_email",
        "license_id",
        "categories",
        "notes",
        "tags",
        "state",
        "from_time",
        "to_time",
        "url_website",
        "extras",
        "categories",
        "fields_description"
    );
    
    public $resourceAttributes = array(
        "url",
        "name",
        "description",
        "visualization",
        "code",
        "documentation",
        "format",
        "mimetype",
        "mimetype-inner",
        "size",
        "last_modified",
        "hash",
        "resource_type",
        "package_id",
    );
        
    
    public function __construct( eZContentObject $object, array $resources )
    {
        $this->object = $object;
        $this->dataMap = $object->attribute( 'data_map' );
        $this->resources = $resources;
    }
    
    public function convert()
    {
        foreach( $this->datasetMetaAttributes as $datasetMetaAttribute )
        {
            switch( $datasetMetaAttribute )
            {
                case 'id':
                    $this->data[$datasetMetaAttribute] = OCOpenDataTools::generateUniqueId( $this->object->attribute( 'id' ) );
                    break;
                
                case 'name':
                    $trans = eZCharTransform::instance();
                    $original = $this->object->attribute( 'name' );
                    $name = $trans->transformByGroup( $original, 'urlalias' );
                    $this->data[$datasetMetaAttribute] = $name;
                    break;
                
                case 'metadata_created':
                    $this->data[$datasetMetaAttribute] = $this->object->attribute( 'published' );
                    break;
                
                case 'metadata_modified':
                    $this->data[$datasetMetaAttribute] = $this->object->attribute( 'modified' );
                    break;
                
                case 'version':
                    $this->data[$datasetMetaAttribute] = $this->object->attribute( 'current_version' );
                    break;
                
                case 'url':
                    //$url = 'content/view/full/' . $this->object->attribute( 'main_node_id' );
                    $url = $this->object->attribute( 'main_node' )->attribute( 'url_alias' );
                    eZURI::transformURI( $url, false, 'full' );
                    $this->data[$datasetMetaAttribute] = $url;
                    break;
                
            }
        }
        
        foreach( $this->datasetAttributes as $datasetAttribute )
        {
            switch( $datasetAttribute )
            {                
                case 'author':
                    if ( isset( $this->dataMap['author'] ) && $this->dataMap['author']->attribute( 'has_content' ) )
                    {
                        $author = explode( '|', $this->dataMap['author']->toString() );
                        $this->data[$datasetAttribute] = $author[0];
                    }
                    break;
                
                case 'author_email':
                    if ( isset( $this->dataMap['author'] ) && $this->dataMap['author']->attribute( 'has_content' ) )
                    {
                        $author = explode( '|', $this->dataMap['author']->toString() );
                        $this->data[$datasetAttribute] = $author[1];
                    }
                    break;
                
                case 'maintainer':
                    if ( isset( $this->dataMap['maintainer'] ) && $this->dataMap['maintainer']->attribute( 'has_content' ) )
                    {
                        $maintainer = explode( '|', $this->dataMap['maintainer']->toString() );
                        $this->data[$datasetAttribute] = $maintainer[0];
                    }
                    break;
                
                case 'maintainer_email':
                    if ( isset( $this->dataMap['maintainer'] ) && $this->dataMap['maintainer']->attribute( 'has_content' ) )
                    {
                        $maintainer = explode( '|', $this->dataMap['maintainer']->toString() );
                        $this->data[$datasetAttribute] = $maintainer[1];
                    }
                    break;
                
                case 'url_website':
                    if ( isset( $this->dataMap['url_website'] ) && $this->dataMap['url_website']->attribute( 'has_content' ) )
                    {
                        $url = explode( '|', $this->dataMap['url_website']->toString() );
                        $this->data[$datasetAttribute] = $url[0];
                    }
                    break;
                
                case 'categories':
                    if ( isset( $this->dataMap['categories'] ) && $this->dataMap['categories']->attribute( 'has_content' ) )
                    {
                        $categories = explode( '|', $this->dataMap['categories']->toString() );
                        $this->data[$datasetAttribute] = implode( ', ', $categories );
                    }
                    break;
                
                case 'fields_description':
                    if ( isset( $this->dataMap['fields_description'] ) && $this->dataMap['fields_description']->attribute( 'has_content' ) )
                    {
                        $fields = $keys = array();
                        $columns = $this->dataMap['fields_description']->content()->attribute( 'columns' );
                        foreach( $columns['sequential'] as $column )
                        {
                            $keys[] = $column['identifier'];
                        }
                        $rows = $this->dataMap['fields_description']->content()->attribute( 'rows' );
                        foreach( $rows['sequential'] as $row )
                        {
                            $fields[] = array_combine( $keys, $row['columns'] );
                        }
                        $this->data[$datasetAttribute] = $fields;
                    }
                    break;
                
                
                default:
                    if ( isset( $this->dataMap[$datasetAttribute] ) && $this->dataMap[$datasetAttribute]->attribute( 'has_content' ) )
                    {
                        $this->data[$datasetAttribute] = $this->dataMap[$datasetAttribute]->toString();
                    }
                    break;
            }
        }
        
        $this->data['resources'] = array();
        foreach( $this->resources as $number => $resource )
        {
            $this->data['resources'][] = $this->convertResource( $resource );
        }        
        
        return $this;
    }
    
    public function convertResource( array $resource )
    {
        $data = array();
        foreach( $this->resourceAttributes as $resourceAttribute )
        {
            switch( $resourceAttribute )
            {                
                case 'url':
                    if ( isset( $resource['file'] ) )
                    {                        
                        $url = $resource['file']->content()->attribute( 'filepath' );
                        eZURI::transformURI( $url, false, 'full' );                        
                        $resourceType = 'file';                                                
                        //$data["hash"] = null;
                        $data["size"] = $resource['file']->content()->attribute( 'filesize' );
                        $data["mimetype"] = $resource['file']->content()->attribute( 'mime_type' );
                        $format = eZFile::suffix( $resource['file']->content()->attribute( 'filepath' ) );
                    }
                    elseif ( isset( $resource['api'] ) )
                    {
                        $url = $resource['api']->toString();
                        $resourceType = 'api';
                    }
                    elseif ( isset( $resource['url'] ) )
                    {                        
                        $url = explode( '|', $resource['url']->toString() );
                        $url = $url[0];
                        eZURI::transformURI( $url, false, 'full' );                        
                        $resourceType = 'file';
                    }
                    
                    $data[$resourceAttribute] = $url;
                    break;
                
                default:
                    if ( isset( $resource[$resourceAttribute] ) && $resource[$resourceAttribute]->attribute( 'has_content' ) )
                    {
                        $data[$resourceAttribute] = $resource[$resourceAttribute]->toString();
                    }
                    break;
            }
        }
        
        if ( !isset( $data['format'] ) )
            $data['format'] = $format;
        
        if ( !isset( $data['resource_type'] ) )
            $data['resource_type'] = $resourceType;
        
        if ( !isset( $data['package_id'] ) )
            $data['package_id'] = OCOpenDataTools::generateUniqueId( $this->object->attribute( 'id' ) );
        
        return $data;
    }
    
    public function getData()
    {        
        $this->convert();        
        return $this->data;
    }
}
