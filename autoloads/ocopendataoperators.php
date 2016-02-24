<?php

class OCOpenDataOperators
{
    static $operators = array(
        'fetch_licenses' => array(),
        'fetch_charsets' => array(),
    );

    function operatorList()
    {
        return array_keys( self::$operators );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return self::$operators;
    }

    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch ($operatorName)
        {
            case 'fetch_charsets':
                $returnArray = mb_list_encodings();
                $operatorValue = $returnArray;
                break;
            
            case 'fetch_licenses':
                $openDataTools = new OCOpenDataTools();
                $licenses = $openDataTools->getLicenseList();
                $returnArray = array();        
                foreach( $licenses as $license )
                {
                    $returnArray[$license->id] = $license->title;
                }
                $operatorValue = $returnArray;
                break;
        }
    }

}
?>