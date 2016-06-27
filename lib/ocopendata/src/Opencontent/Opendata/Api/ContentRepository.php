<?php

namespace Opencontent\Opendata\Api;

use eZContentObject;
use Opencontent\Opendata\Api\Exception\CreateContentException;
use Opencontent\Opendata\Api\Exception\DuplicateRemoteIdException;
use Opencontent\Opendata\Api\Gateway\FileSystem;
use Opencontent\Opendata\Api\Gateway\Database;
use Opencontent\Opendata\Api\Gateway\SolrStorage;
use Opencontent\Opendata\Api\Exception\ForbiddenException;
use Opencontent\Opendata\Api\Structs\ContentCreateStruct;
use Opencontent\Opendata\Api\Structs\ContentUpdateStruct;
use Opencontent\Opendata\Api\Values\Content;

class ContentRepository
{
    /**
     * @var EnvironmentSettings
     */
    protected $currentEnvironmentSettings;

    public function __construct()
    {
        //        $this->gateway = new Database();      // fallback per tutti
        //        $this->gateway = new SolrStorage();   // usa solr storage per restituire oggetti (sembra lento...)
        $this->gateway = new FileSystem();      // scrive cache sul filesystem (cluster safe)
    }

    public function setEnvironment(EnvironmentSettings $environmentSettings)
    {
        $this->currentEnvironmentSettings = $environmentSettings;
    }

    /**
     * @param $content
     *
     * @return array
     * @throws ForbiddenException
     */
    public function read($content)
    {
        if (!$content instanceof Content) {
            $content = $this->gateway->loadContent($content);
        }

        if (!$content->canRead()) {
            throw new ForbiddenException($content, 'read');
        }

        return $this->currentEnvironmentSettings->filterContent($content);
    }

    public function create($payload)
    {
        $createStruct = $this->currentEnvironmentSettings->instanceCreateStruct($payload);
        $createStruct->validate();
        $createStruct->checkAccess(\eZUser::currentUser());
        $publicationProcess = new PublicationProcess($createStruct);
        $contentId = $publicationProcess->publish();

        $this->currentEnvironmentSettings->afterCreate($contentId, $createStruct);

        return array(
            'message' => 'success',
            'method' => 'create',
            'content' => (array)$this->read($contentId)
        );
    }

    public function update($payload)
    {
        $updateStruct = $this->currentEnvironmentSettings->instanceUpdateStruct($payload);
        $updateStruct->validate();
        $updateStruct->checkAccess(\eZUser::currentUser());
        $publicationProcess = new PublicationProcess($updateStruct);
        $contentId = $publicationProcess->publish();

        $this->currentEnvironmentSettings->afterUpdate($contentId, $updateStruct);

        return array(
            'message' => 'success',
            'method' => 'update',
            'content' => (array)$this->read($contentId)
        );
    }

    public function createUpdate($payload){
        try {
            $result = $this->create($payload);
        } catch (DuplicateRemoteIdException $e) {
            $result = $this->update($payload);
        }

        return $result;
    }

    public function delete($data)
    {
        return 'todo';
    }
}
