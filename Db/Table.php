<?php
class App_Db_Table extends Zend_Db_Table
{
	/**
     * Classname for row
     *
     * @var string
     */
    protected $_rowClass = 'App_Db_Table_Row';
	protected $_rowsetClass = 'App_Db_Table_Rowset';
	
	protected $_relationMap = array();
	
	public function getRelation($relName)
	{
        if(!isset($this->_relationMap[$relName])) {
            require_once "Zend/Db/Table/Exception.php";
            throw new Zend_Db_Table_Exception('No relation named ' . $relName);
        }
        
        return $this->relationMap[$relName];
	}
}