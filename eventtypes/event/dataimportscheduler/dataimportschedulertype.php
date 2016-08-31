<?php

class DataImportSchedulerType extends eZWorkflowEventType
{
  const WORKFLOW_TYPE_STRING = "dataimportscheduler";

  function DataImportSchedulerType()
    {
      $this->eZWorkflowEventType( DataImportSchedulerType::WORKFLOW_TYPE_STRING, ezpI18n::tr( 'opencontent', 'Opendata import scheduler' ) );
      $this->setTriggerTypes( array( 'content' => array( 'publish' => array( 'after' ) ) ) );
    }
    
    function execute( $process, $event )
    {

        $parameterList = $process->attribute( 'parameter_list' );

        $objectID = $parameterList['object_id'];
        $object = eZContentObject::fetch( $objectID );
        
        if ( $object instanceof eZContentObject )
        {
          if ( $object->attribute( 'class_identifier' ) == 'data_import_scheduler' )
          {
              $importHandler = new DataImportSchedulerHandler();
              $importHandler->scheduleImport( $object );
          }
        }
        
        return eZWorkflowType::STATUS_ACCEPTED;
    }

}

eZWorkflowEventType::registerEventType( DataImportSchedulerType::WORKFLOW_TYPE_STRING, 'DataImportSchedulerType' );

?>