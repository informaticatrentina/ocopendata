<?php

use Opencontent\Opendata\Rest\Client\HttpClient;
use Opencontent\Opendata\Rest\Client\PayloadBuilder;
use Opencontent\Opendata\Api\ContentRepository;

$module = $Params['Module'];
$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();
$data = null;

$url = 'https://www.ufficiostampa.provincia.tn.it/var/002/storage/images/media/immagini-comunicati-stampa/sindaco-paolo-forno-presidente-ugo-rossi-assessore-carlo-daldoss-image/651042-1-ita-IT/sindaco-Paolo-Forno-presidente-Ugo-Rossi-assessore-Carlo-Daldoss.jpg';
$result = tempFile( $url);


var_dump($result);
exit;


$host = "https://www.ufficiostampa.provincia.tn.it";
$query= "classes 'comunicato' and persone.id = '11351' and published range [last week, tomorrow] sort [published=>desc] limit 1";

$client = new HttpClient( $host );
$data = $client->findAll( $query );

$payload = $client->getPayload($data[0]);

echo '<pre>';


$payload->setData(null, 'immagini', uploadFiles($payload->getData('immagini'), $client));

function tempFile( $url )
{
    if ( OpenPABase::getDataByURL( $url, true ) )
    {
        $tempVarDir = eZINI::instance()->variable( 'FileSettings','VarDir' ) . '/import/';
        eZDir::mkdir( $tempVarDir );
        $name = basename( $url );
        $file = eZFile::create( $name, $tempVarDir, OpenPABase::getDataByURL( $url ) );
        $filePath = rtrim( $tempVarDir, '/' ) . '/' . $name;
        //$this->removeFiles[] = $filePath;
        return $filePath;
    }
    else
    {
        return null;
        //throw new AlboFatalException( "File {$url} non trovato" );
    }
}

function uploadFiles( $files, HttpClient $client )
{

    $objectIDs = array();
    foreach( array_values($files)[0] as $file )
    {
        $filePayload = $client->getPayload($file['id']);
        $fileData = array_values($filePayload['data'])[0];

        $url = $fileData['image']['url'];

        if ( OpenPABase::getDataByURL( $url, true ) )
        {
            $data = OpenPABase::getDataByURL( $url );
            $remoteID = md5($url);
            $node = false;
            $object = eZContentObject::fetchByRemoteID($remoteID);
            if ($object instanceof eZContentObject) {
                $node = $object->attribute('main_node');
            }
            $name = $fileData['image']['filename'];
            $fileStored = tempFile($url);
            if ($fileStored !== null) {
                $result = array();
                $upload = new eZContentUpload();
                $uploadFile = $upload->handleLocalFile($result, $fileStored, 'auto', $node, $name);
                if (isset($result['contentobject']) && (!$object instanceof eZContentObject)) {
                    $object = $result['contentobject'];
                    $object->setAttribute('remote_id', $remoteID);
                    $object->store();
                } elseif (isset($result['errors']) && !empty($result['errors'])) {
                    throw new Exception(implode(', ', $result['errors']));
                }

                if ($object instanceof eZContentObject) {
                    $objectIDs[] = $object->attribute('id');
                    //$this->removeObjects[] = $object;
                } else {
                    throw new Exception('Errore caricando ' . var_export($file, 1) . ' ' . $fileStored);
                }
            }
        }
    }
    return implode( '-', $objectIDs );
}


eZExecution::cleanExit();
exit;