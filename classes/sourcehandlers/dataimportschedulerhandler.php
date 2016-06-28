<?php

class DataImportSchedulerHandler
{
    public $scheduledItems;
    
    function __construct()
    {
        $this->scheduledItems = array();
        foreach( SQLIScheduledImport::fetchList() as $schedule )
        {
            $this->scheduledItems[$schedule->attribute( 'label' )] = $schedule;
        }
    }
    
    public function scheduleImport( eZContentObject $object )
    {

        $list = '';

        if ( $list !== '' )
        {
            $importID = 0;
            $importLabel = $object->attribute( 'name' );

            $data_map = $object->dataMap();

            if ( array_key_exists( $importLabel, $this->scheduledItems ) )
            {
                $importID = $this->scheduledItems[$importLabel]->attribute( 'id' );
            }

            $importOptions = new SQLIImportHandlerOptions( array(
                'parentnodeid' => 1279,
                'title' => $data_map['title'],
                'query' => $data_map['query'],
                'start_date' => $data_map['start_date'],
                'frequency' => $data_map['frequency'],
                'parent_node' => $data_map['parent_node'],
                'class' => $data_map['class']
            ) );

            $currentImportHandler = 'dataimportschedulerimporthandler';
            $importFrequency = 'daily';

            $row = array(
                'handler'   => $currentImportHandler,
                'user_id'   => eZUser::currentUserID(),
                'label'     => $importLabel,
                'frequency' => $importFrequency,
                'next'      => time(),
                'is_active' => 1
            );

            $scheduledImport = SQLIScheduledImport::fetch( $importID );
            if ( !$scheduledImport instanceof SQLIScheduledImport )
            {
                $scheduledImport = new SQLIScheduledImport( $row );
            }
            else
            {
                $scheduledImport->fromArray( $row );
            }

            if ( $importOptions )
            {
                $scheduledImport->setAttribute( 'options', $importOptions );
            }

            $scheduledImport->store();
        }
    }
}

?>