<?php

use Opencontent\Opendata\Api\ContentSearch;
use Opencontent\Opendata\Api\ClassRepository;
use Opencontent\Opendata\Api\AttributeConverterLoader;

class SearchQueryCSVExporter extends AbstarctExporter
{

    /**
     * @var ContentSearch
     */
    protected $contentSearch;

    /**
     * @var ClassRepository
     */
    protected $classRepository;

    protected $queryString;

    protected $CSVheaders;

    protected $uniqueClassIdentifier;

    protected $language;

    protected $classFields = array();

    public function __construct($parentNodeId, $queryString)
    {
        $this->functionName = 'csv';

        $this->ini = eZINI::instance('exportas.ini');
        $this->setOptions($this->ini->group('Settings'));

        $currentEnvironment = new CsvEnvironmentSettings;
        $currentEnvironment->__set('identifier', 'csv');
        $currentEnvironment->__set('debug', false);
        $this->contentSearch = new ContentSearch();
        $this->contentSearch->setEnvironment($currentEnvironment);
        $this->queryString = $queryString;
        $this->filename = uniqid('export_');

        $this->classRepository = new ClassRepository();

        $this->language = eZLocale::currentLocaleCode();
    }

    function transformNode(eZContentObjectTreeNode $node)
    {
        return null;
    }

    public function fetch()
    {
        return $this->contentSearch->search($this->queryString);
    }

    public function fetchCount()
    {
        $result = $this->contentSearch->search($this->queryString);

        return $result->totalCount;
    }

    protected function csvHeaders($item)
    {
        $this->uniqueClassIdentifier = $item['metadata']['classIdentifier'];
        $class = $this->classRepository->load($this->uniqueClassIdentifier);
        $this->CSVheaders = array();
        foreach ($class->fields as $field) {

            $header = $this->csvHeader($field);

            if (is_string($header)) {
                $this->CSVheaders[] = $header;
                $this->classFields[$field['identifier']] = $field['identifier'];
            } else {
                $this->CSVheaders = array_merge($this->CSVheaders, $header);
            }
        }

        return $this->CSVheaders;
    }

    protected function csvHeader($field)
    {
        $header = $field['name'][$this->language];
        switch ($field['dataType']) {

            case 'ezmatrix': {
                $baseHeader = $header;
                $header = array();
                $fieldIdentifiers = array();
                foreach ($field['template']['format'][0][0] as $key => $value) {
                    $columnHeader = str_replace('string (', ' (', $value);
                    $header[] = $baseHeader . $columnHeader;
                    $fieldIdentifiers[] = $key;
                }
                $this->classFields[$field['identifier']] = $fieldIdentifiers;
            }
                break;
        }

        return $header;
    }

    function transformItem($item)
    {
        $data = $item['data'][$this->language];

        if ($this->uniqueClassIdentifier === null) {
            $this->uniqueClassIdentifier = $item['metadata']['classIdentifier'];
        } else {
            if ($this->uniqueClassIdentifier != $item['metadata']['classIdentifier']) {
                throw new Exception("Multiple class export not allowed");
            }
        }

        $stringData = array();
        foreach ($data as $key => $field) {
            list( $classIdentifier, $identifier ) = explode('/', $field['identifier']);
            $converter = AttributeConverterLoader::load(
                $classIdentifier,
                $identifier,
                $field['datatype']
            );
            switch ($field['datatype']) {

                case 'ezmatrix': {
                    foreach ($this->classFields[$identifier] as $columnIdentifier) {
                        $stringData[$key . '.' . $columnIdentifier] = $converter->toCSVString(
                            $field['content'],
                            $columnIdentifier
                        );
                    }
                }
                    break;

                case 'ezobjectrelation':
                case 'ezobjectrelationlist': {
                    $stringData[$key] = $converter->toCSVString($field['content'], $this->language);
                }
                    break;

                default: {
                    $stringData[$key] = $converter->toCSVString($field['content']);
                }
                    break;
            }
        }
        return $stringData;
    }

    function handleDownload()
    {
        try {

            $filename = $this->filename . '.csv';
            header('X-Powered-By: eZ Publish');
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=$filename");
            header("Pragma: no-cache");
            header("Expires: 0");

            $output = fopen('php://output', 'w');
            $runOnce = false;

            do {
                $result = $this->fetch();
                foreach ($result->searchHits as $item) {
                    if (!$runOnce) {
                        $this->csvHeaders($item);
                        fputcsv(
                            $output,
                            $this->csvHeaders($item),
                            $this->options['CSVDelimiter'],
                            $this->options['CSVEnclosure']
                        );
                        $runOnce = true;
                    }
                    $values = $this->transformItem($item);
                    fputcsv($output, $values, $this->options['CSVDelimiter'], $this->options['CSVEnclosure']);
                    flush();
                }
                $this->queryString = $result->nextPageQuery;

            } while ($this->queryString != null);

        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }
}