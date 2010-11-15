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
	
	public function getReferenceByRuleKey($ruleKey)
	{
		$refMap = $this->_getReferenceMapNormalized();
        if(!isset($refMap[$ruleKey])) {
            require_once "Zend/Db/Table/Exception.php";
            throw new Zend_Db_Table_Exception("No reference rule \"$ruleKey\" from table $thisClass to table $tableClassname");
        }
        
        return $refMap[$ruleKey];
	}
}