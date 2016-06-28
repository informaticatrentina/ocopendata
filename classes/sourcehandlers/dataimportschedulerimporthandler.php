<?php
 
class DataImportSchedulerImportHandler extends SQLIImportAbstractHandler implements ISQLIImportHandler
{
    protected $rowIndex = 0;
    protected $rowCount;
    protected $currentGUID;

    /**
     * Constructor
     */
    public function __construct( SQLIImportHandlerOptions $options = null )
    {   

        try{

            parent::__construct( $options );

        } 
        catch (Exception $e) {

        }
    }
    
    public function initialize() {

        try{

            $this->dataSource = array();
        } 
        catch (Exception $ex) {

        }       
    }

    public function getProcessLength() {

        if( !isset( $this->rowCount ) )
        {   
            $this->rowCount = count($this->dataSource);
            if ($this->rowCount == 0)
                $this->rowCount = -1;
        }  
        else {
            $this->rowCount = -1;
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

            $row;

        }catch (Exception $e){

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
    
}
?>