<?php

use Opencontent\Opendata\Rest\Client\HttpClient;
use Opencontent\Opendata\Rest\Client\PayloadBuilder;
use Opencontent\Opendata\Api\ContentRepository;


class DataImportSchedulerImportHandler extends SQLIImportAbstractHandler implements ISQLIImportHandler
{
    protected $rowIndex = 0;
    protected $rowCount;
    protected $currentGUID;

    protected $client;
    protected $repository;

    protected $query;

    /**
     * Constructor
     */
    public function __construct( SQLIImportHandlerOptions $options = null )
    {
        $this->repository = new ContentRepository();
        $this->repository->setEnvironment(new DefaultEnvironmentSettings());

        try{
            parent::__construct( $options );
            $host = $this->options['host'];
            $this->client = new HttpClient($host);
            $this->query = $this->options['query'];
        } catch (Exception $e) {
            $this->cli = $e->getMessage();
        }
    }

    public function initialize()
    {
        try{
            
            $user = eZUser::fetchByName( 'admin' );
            eZUser::setCurrentlyLoggedInUser( $user , $user->attribute( 'contentobject_id' ) );

            $this->dataSource = $this->client->findAll($this->query);
        } catch (Exception $ex) {
            $this->cli = $ex->getMessage();
        }
    }

    public function getProcessLength()
    {
        if( !isset( $this->rowCount ) )
        {
            $this->rowCount = count( $this->dataSource );
        }
        return $this->rowCount;
    }


    public function getNextRow() {
        try{

            if( $this->rowIndex < $this->rowCount )
            {
                $row = $this->dataSource[$this->rowIndex];
                $this->rowIndex++;
            }
            else
            {
                $row = false;
            }
        }catch (Exception $e){
            $row = false;
        }
        return $row;

    }

    public function cleanup()
    {

        return;
    }

    public function process($row) {

        try{

            $options = $this->options;
            $cli = $this->cli;
            $client = $this->client;
            $repository = $this->repository;

            $import = $client->import( $row, $repository,
                function(PayloadBuilder $payload, $client, $repository) use($options){

                    $payload->setClassIdentifier($options['class']);
                    $payload->setParentNodes(array($options['parent_node']));

                    //modified non funziona perchè non viene passata nel playload ma lo loascio comunque per predisporre eventuali modifiche future
                    $payload->setModified($this->createDate($payload->getData('modified')));

                    $payload->setPublished($this->createDate($payload->getData('published')));

                    $payload->unSetData('autore');
                    $payload->unSetData('non_inviare');
                    $payload->unSetData('internal_comments');
                    $payload->unSetData('riferimenti_strutturati');
                    $payload->unSetData('riferimenti_strutturati_as_string');
                    $payload->unSetData('fonte');
                    $payload->unSetData('argomento');
                    $payload->unSetData('tags');
                    //$payload->unSetData('tematica');
                    /*$payload->unSetData('allegati');
                    $payload->unSetData('video');
                    $payload->unSetData('audio');
                    $payload->unSetData('immagini');*/
                    $payload->setData(null, 'tematica', $this->assignTags($payload->getData('tematica')));
                    $payload->setData(null, 'allegati', $this->uploadFiles($payload->getData('allegati')));
                    $payload->setData(null, 'video', $this->uploadFiles($payload->getData('video')));
                    $payload->setData(null, 'audio', $this->uploadFiles($payload->getData('audio')));
                    $payload->setData(null, 'immagini', $this->uploadFiles($payload->getData('immagini')));
                    $payload->unSetData('luogo');
                    $payload->unSetData('persone');
                    $payload->unSetData('punto');
                    return $payload;
                }
            );

            if ($import['message'] == 'success') {
                $cli->warning( "Import " . $import['content']['metadata']['name']['ita-IT'] );
            } else {
                $cli->warning( "Error importing " . $row['metadata']['name']['ita-IT'] . " " . $import['message'] );
            }
        }catch (Exception $e){
            $this->cli->error($e->getMessage());
        }
    }

    public function getHandlerIdentifier() {
        return 'dataimportschedulerimporthandler';

    }

    public function getHandlerName() {
        return 'dataimportschedulerimport handler';
    }

    public function getProgressionNotes()
    {
        return 'Currently importing : '.$this->currentGUID;
    }

    public function tempFile( $url )
    {
        if ( OpenPABase::getDataByURL( $url, true ) )
        {
            $tempVarDir = eZINI::instance()->variable( 'FileSettings','VarDir' ) . '/import/';
            eZDir::mkdir( $tempVarDir );
            $name = basename( $url );
            $file = eZFile::create( $name, $tempVarDir, OpenPABase::getDataByURL( $url, false, false, 1 , 10 ) );
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

    public function uploadFiles( $files )
    {
        $objectIDs = array();
        foreach( array_values($files)[0] as $file )
        {
            $filePayload = $this->client->getPayload($file['id']);
            $fileData = array_values($filePayload['data'])[0];

            $url = isset($fileData['image']) ? $fileData['image']['url'] : $fileData['file']['url'];

            if ( OpenPABase::getDataByURL( $url, true ) )
            {
                $data = OpenPABase::getDataByURL( $url );
                $remoteID = md5($url);
                $node = false;
                $object = eZContentObject::fetchByRemoteID($remoteID);
                if ($object instanceof eZContentObject) {
                    $node = $object->attribute('main_node');
                }
                $name = $fileData['name'];
                $fileStored = $this->tempFile($url);
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
        return ($objectIDs);
        //return implode( '-', $objectIDs );
    }

    public function assignTags( $tags )
    {
        $keywordArray = explode(",", array_values($tags)[0]);
        $result = array();

        // Recupero la lista dei tags
        foreach ($keywordArray as $key)
        {

            //$key = $this->fixEncoding( trim(iconv(mb_detect_encoding($key, mb_detect_order(), true), "UTF-8", $key)) );
            $result []= trim( $this->fixEncoding($this->remove_accents($key)) );
        }
        return $result;
    }

    public function fixEncoding( $string )
    {
        $currentEncoding = mb_detect_encoding( $string ) ;
        if( $currentEncoding == "UTF-8" && mb_check_encoding( $string, "UTF-8" ) )
            return $string;
        else
            return utf8_encode( $string );
    }

    /**
     * Converts all accent characters to ASCII characters.
     *
     * If there are no accent characters, then the string given is just returned.
     *
     * @since 1.2.1
     *
     * @param string $string Text that might have accent characters
     * @return string Filtered string with replaced "nice" characters.
     */
    public function remove_accents($string) {
        if ( !preg_match('/[\x80-\xff]/', $string) )
            return $string;

        if ($this->seems_utf8($string)) {
            $chars = array(
                // Decompositions for Latin-1 Supplement
                chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
                chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
                chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
                chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
                chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
                chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
                chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
                chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
                chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
                chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
                chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
                chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
                chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
                chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
                chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
                chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
                chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
                chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
                chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
                chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
                chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
                chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
                chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
                chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
                chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
                chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
                chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
                chr(195).chr(191) => 'y',
                // Decompositions for Latin Extended-A
                chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
                chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
                chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
                chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
                chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
                chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
                chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
                chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
                chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
                chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
                chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
                chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
                chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
                chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
                chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
                chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
                chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
                chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
                chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
                chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
                chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
                chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
                chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
                chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
                chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
                chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
                chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
                chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
                chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
                chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
                chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
                chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
                chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
                chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
                chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
                chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
                chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
                chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
                chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
                chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
                chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
                chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
                chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
                chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
                chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
                chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
                chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
                chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
                chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
                chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
                chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
                chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
                chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
                chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
                chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
                chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
                chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
                chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
                chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
                chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
                chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
                chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
                chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
                chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
                // Euro Sign
                chr(226).chr(130).chr(172) => 'E',
                // GBP (Pound) Sign
                chr(194).chr(163) => '');

            $string = strtr($string, $chars);
        } else {
            // Assume ISO-8859-1 if not UTF-8
            $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
                .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
                .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
                .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
                .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
                .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
                .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
                .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
                .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
                .chr(252).chr(253).chr(255);

            $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
            $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }

    /**
     * Checks to see if a string is utf8 encoded.
     *
     * NOTE: This function checks for 5-Byte sequences, UTF8
     *       has Bytes Sequences with a maximum length of 4.
     *
     * @author bmorel at ssi dot fr (modified)
     * @since 1.2.1
     *
     * @param string $str The string to be checked
     * @return bool True if $str fits a UTF-8 model, false otherwise.
     */
    public function seems_utf8($str) {
        $length = strlen($str);
        for ($i=0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) $n = 0; # 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
            elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
            elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
            elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
            elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
            else return false; # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                    return false;
            }
        }
        return true;
    }

    public function createDate($inputDate, $format=null){

        if($inputDate==null){
            return null;
        }

        $inputDate = $inputDate['ita-IT'];

        //2016-07-31T14:56:02+02:00
        if($format==null){
            $format = 'Y-m-d\TH:i:sP';

        }

        $dateTime = DateTime::createFromFormat($format, $inputDate);

        if ( $dateTime instanceOf DateTime ){

            $timestamp = $dateTime->format('U');

        }else{

            throw new Exception( 'Errore nella conversione della data: '.$inputDate );
        }

        return $timestamp;
    }

}
?>
