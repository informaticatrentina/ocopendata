<?php

namespace Opencontent\Opendata\Api\Structs;

use Opencontent\Opendata\Api\ClassRepository;
use Opencontent\Opendata\Api\StateRepository;
use Opencontent\Opendata\Api\SectionRepository;
use Opencontent\Opendata\Api\Exception\CreateContentException as Exception;
use eZContentObject;
use eZContentObjectTreeNode;
use eZContentLanguage;
use Opencontent\Opendata\Api\Exception\OutOfRangeException;
use Opencontent\Opendata\Api\Values\ContentClass;
use Opencontent\Opendata\Api\Values\ContentSection;
use Opencontent\Opendata\Api\Values\ContentState;


class MetadataCreateStruct implements \ArrayAccess
{
    public $remoteId;

    public $classIdentifier;

    public $sectionIdentifier;

    public $stateIdentifiers;

    public $parentNodes;

    public $languages;

    /**
     * @var ContentClass
     */
    protected $class;

    /**
     * @var ContentSection
     */
    protected $section;

    /**
     * @var ContentState[]
     */
    protected $states = array();


    /**
     * @var eZContentObjectTreeNode[]
     */
    protected $nodes = array();


    /**
     * @var StateRepository
     */
    protected $stateRepository;

    /**
     * @var SectionRepository
     */
    protected $sectionRepository;

    /**
     * @var ClassRepository
     */
    protected $classRepository;

    protected $useDefaultLanguage = false;

    public function __construct(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->$property = $value;
            } else {
                throw new OutOfRangeException($property);
            }
        }

        $this->stateRepository = new StateRepository();
        $this->sectionRepository = new SectionRepository();
        $this->classRepository = new ClassRepository();

    }

    /**
     * @return StateRepository
     */
    public function getStateRepository()
    {
        return $this->stateRepository;
    }

    /**
     * @param StateRepository $stateRepository
     */
    public function setStateRepository($stateRepository)
    {
        $this->stateRepository = $stateRepository;
    }

    /**
     * @return SectionRepository
     */
    public function getSectionRepository()
    {
        return $this->sectionRepository;
    }

    /**
     * @param SectionRepository $sectionRepository
     */
    public function setSectionRepository($sectionRepository)
    {
        $this->sectionRepository = $sectionRepository;
    }

    /**
     * @return ClassRepository
     */
    public function getClassRepository()
    {
        return $this->classRepository;
    }

    /**
     * @param ClassRepository $classRepository
     */
    public function setClassRepository($classRepository)
    {
        $this->classRepository = $classRepository;
    }

    /**
     * @return ContentClass
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param ContentClass $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return ContentSection
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param ContentSection $section
     */
    public function setSection($section)
    {
        $this->section = $section;
    }

    /**
     * @return ContentState[]
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @param ContentState[] $states
     */
    public function setStates($states)
    {
        $this->states = $states;
    }

    public function useDefaultLanguage()
    {
        return $this->useDefaultLanguage;
    }

    public function validate()
    {
        // remoteId univoco
        if ($this->remoteId !== null && eZContentObject::fetchByRemoteID($this->remoteId)) {
            throw new Exception("Remote '{$this->remoteId}' already exists");
        }

        // classe
        if ($this->classIdentifier === null) {
            throw new Exception("Missing parameter 'classIdentifier'");
        }
        $this->class = $this->classRepository->load($this->classIdentifier);
        if (!$this->class instanceof ContentClass) {
            throw new Exception("Class '{$this->classIdentifier}' not found");
        }

        // sezione
        if ($this->sectionIdentifier !== null) {
            if (!is_string($this->sectionIdentifier)) {
                throw  new Exception("sectionIdentifier must be a string: a single section for a single content");
            }
            $this->section = $this->sectionRepository->load($this->sectionIdentifier);
            if (!$this->section instanceof ContentSection) {
                throw new Exception("Section '{$this->sectionIdentifier}' not found");
            }
        }

        // stati
        if ($this->stateIdentifiers !== null) {
            if (is_string($this->stateIdentifiers)) {
                $this->stateIdentifiers = array($this->stateIdentifiers);
            }

            foreach ($this->stateIdentifiers as $stateIdentifier) {
                $this->states[$stateIdentifier] = $this->stateRepository->load($stateIdentifier);
                if (!$this->states[$stateIdentifier] instanceof ContentState) {
                    throw new Exception("Section '{$stateIdentifier}' not found");
                }
            }
        }else{
            $this->stateIdentifiers = array();
        }

        // lingue
        if ($this->languages !== null) {
            $languages = eZContentLanguage::fetchLocaleList();
            foreach ($this->languages as $language) {
                if (!in_array($language, $languages)) {
                    throw new Exception("Language '{$language}' not found");
                }
            }
        } else {
            $prioritizedLanguageCodes = eZContentLanguage::prioritizedLanguageCodes();
            $this->languages = array($prioritizedLanguageCodes[0]);
            $this->useDefaultLanguage = true;
        }

        //parent nodes
        if ($this->parentNodes == null) {
            throw new Exception("Missing parameter 'parentNodes'");
        }

        if (!is_array($this->parentNodes)) {
            $this->parentNodes = array($this->parentNodes);
        }

        foreach ($this->parentNodes as $nodeId) {
            $node = eZContentObjectTreeNode::fetch((int)$nodeId);
            if ($node instanceof eZContentObjectTreeNode) {
                $this->nodes[] = $node;
            } else {
                throw new Exception("Node '{$nodeId}' not found");
            }
        }

    }

    public function checkAccess()
    {
        foreach ($this->nodes as $parentNode) {
            $parentObject = $parentNode->object();
            foreach ($this->languages as $languageCode) {
                if ($parentObject->checkAccess(
                        'create',
                        $this->class->getClassId(),
                        false,
                        false,
                        $languageCode) != '1'
                ) {
                    throw new Exception("Current user can not create '{$this->class->identifier}' objects in language '$languageCode' in node #{$parentNode->attribute( 'node_id' )}");
                }
            }
        }

        $allowedAssignStateList = $this->getAllowedAssignStateList();
        foreach( $this->stateIdentifiers as $stateIdentifier )
        {
            if ( !in_array( $stateIdentifier, $allowedAssignStateList ) ){
                throw new Exception("Current user can not create object in state '$stateIdentifier'");
            }
        }

    }

    public function offsetExists($property)
    {
        return isset( $this->{$property} );
    }

    public function offsetGet($property)
    {
        return $this->{$property};
    }

    public function offsetSet($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->{$property} = $value;
        } else {
            throw new OutOfRangeException($property);
        }
    }

    public function offsetUnset($property)
    {
        $this->{$property} = null;
    }

    protected function getAllowedAssignStateList()
    {
        $user = \eZUser::currentUser();
        $access = $user->hasAccessTo('state', 'assign');

        $db = \eZDB::instance();
        $sql = 'SELECT ezcobj_state.id
                FROM   ezcobj_state, ezcobj_state_group
                WHERE  ezcobj_state.group_id = ezcobj_state_group.id
                    AND ezcobj_state_group.identifier NOT LIKE \'ez%\'';
        if ($access['accessWord'] == 'yes') {
            $allowedStateIDList = $db->arrayQuery($sql, array('column' => 'id'));
        } else if ($access['accessWord'] == 'limited') {
            $userID = $user->attribute('contentobject_id');
            $classID = $this->class->getClassId();
            $ownerID = $userID;
            $sectionID = $this->section['id'];

            $stateIDArray = array();
            foreach ($this->stateRepository->defaultStates() as $state) {
                $stateIDArray[] = $state['id'];
            }

            $allowedStateIDList = array();
            foreach ($access['policies'] as $policy) {
                foreach ($policy as $ident => $values) {
                    $allowed = true;

                    switch ($ident) {
                        case 'Class': {
                            $allowed = in_array($classID, $values);
                        }
                            break;

                        case 'Owner': {
                            $allowed = in_array(1, $values) and $userID != $ownerID;
                        }
                            break;

                        case 'Section':
                        case 'User_Section': {
                            $allowed = in_array($sectionID, $values);
                        }
                            break;

                        //This case is based on the similar if statement in the method : classListFromPolicy
                        case 'User_Subtree': {
                            $allowed = false;
                            foreach ($this->nodes as $assignedNode) {
                                $path = $assignedNode->attribute('path_string');
                                foreach ($policy['User_Subtree'] as $subtreeString) {
                                    if (strpos($path, $subtreeString) !== false) {
                                        $allowed = true;
                                        break;
                                    }
                                }
                            }
                        }
                            break;

                        default: {
                            if (strncmp($ident, 'StateGroup_', 11) === 0) {
                                $allowed = count(array_intersect($values, $stateIDArray)) > 0;
                            }
                        }
                    }

                    if (!$allowed) {
                        continue 2;
                    }
                }

                if (isset( $policy['NewState'] ) and count($policy['NewState']) > 0) {
                    $allowedStateIDList = array_merge($allowedStateIDList, $policy['NewState']);
                } else {
                    $allowedStateIDList = $db->arrayQuery($sql, array('column' => 'id'));
                    break;
                }
            }

            $allowedStateIDList = array_merge($allowedStateIDList, $stateIDArray);
        } else {
            $stateIDArray = array();
            foreach ($this->stateRepository->defaultStates() as $state) {
                $stateIDArray[] = $state['id'];
            }
            $allowedStateIDList = $stateIDArray;
        }

        $allowedStateIDList = array_unique($allowedStateIDList);

        $allowedStateList = array();
        foreach( $allowedStateIDList as $allowedStateID )
        {
            $allowedState = $this->stateRepository->load( $allowedStateID );
            $allowedStateList[] = $allowedState['identifier'];
        }
        sort( $allowedStateList );
        return $allowedStateList;
    }


}