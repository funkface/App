<?php
/**
 * 
 * @author Martin
 * @method App_Db_Table|null getTable() Returns the table object, or null if this is disconnected row
 */
class App_Db_Table_Row extends Zend_Db_Table_Row
{
	
	protected $_memberRowsets = array();
	
	public function __get($columnName)
	{
		$columnName = $this->_transformColumn($columnName);
		
        if(array_key_exists($columnName, $this->_data))
        {
        	return $this->_data[$columnName];
        }
        
        return $this->_getMemberRowset($columnName);
	}
	
	/*
	 * @returns bool
	 */
	public function isModified()
	{
		if(count($this->_modifiedFields) > 0)
		{
			return true;
		}
		
		foreach($this->_memberRowsets as $rowSet)
		{
			if($rowSet->isModified())
			{
				return true;
			}
		}
		
		return false;
	}
	
	public function save()
	{
		if($this->isModified()){
			foreach($this->_memberRowsets as $rowSet)
			{
				$rowSet->save();
			}
			
			return parent::save();
		}
		
		$primaryKey = $this->_getPrimaryKey(true);
        if (count($primaryKey) == 1) {
            return current($primaryKey);
        }

        return $primaryKey;
	}
	
	protected function _getMemberRowset($memberName)
	{
		if(!isset($this->_memberRowsets[$memberName]))
		{
			$this->loadMemberRowset($memberName);
		}
		
		return $this->_memberRowsets[$memberName];
	}
	
	/**
	 * 
	 * @param $memberName
	 * @param $select
	 * @return App_Db_Table_Rowset
	 */
	public function loadMemberRowset($memberName, Zend_Db_Table_Select $select = null)
	{
	    $ref = $this->getTable()->getReferenceByRuleKey($memberName);
        $this->_memberRowsets[$memberName] = 
            $this->findDependentRowset($ref['refTableClass'], $memberName, $select);
            
        return $this->_memberRowsets[$memberName];
	}
	
}