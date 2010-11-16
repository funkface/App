<?php
/**
 *
 * @author Martin
 * @method App_Db_Table|null getTable() Returns the table object, or null if this is disconnected row
 */
class App_Db_Table_Row extends Zend_Db_Table_Row
{
    protected $_memberRows = array();
    protected $_memberRowsets = array();

    public function __get($columnName)
    {
        $columnName = $this->_transformColumn($columnName);

        if(array_key_exists($columnName, $this->_data))
        {
            return $this->_data[$columnName];
        }

        $rel = $this->getTable()->getRelation($columnName);
        
        switch($rel['relation'])
        {
            case 0: // has one
                return $this->_getMemberRow($columnName);
                
            case 1: // has many
            case 2: // many to many
                return $this->_getMemberRowset($columnName);

            default:
                throw new Zend_Db_Table_Row_Exception('Weird relation type' . $rel['relation']);
        }

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
            
            foreach($this->_memberRows as $row)
            {
                $row->save();
            }
            
            $result = parent::save();
            
            foreach($this->_memberRowsets as $rowSet)
            {
                $rowSet->save();
            }
            	
            return $result;
        }

        $primaryKey = $this->_getPrimaryKey(true);
        if (count($primaryKey) == 1) {
            return current($primaryKey);
        }

        return $primaryKey;
    }

    protected function _getMemberRow($memberName)
    {
        if(!isset($this->_memberRows[$memberName]))
        {
            $this->loadMemberRow($memberName);
        }

        return $this->_memberRows[$memberName];
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
     * @param string $memberName
     * @return App_Db_Table_Row
     */
    public function loadMemberRow($memberName)
    {
        $rel = $this->getTable()->getRelation($memberName);

        switch($rel['relation'])
        {
            case 0: // has one
                $this->_memberRows[$memberName] =
                    $this->findParentRow($rel['refTableClass'], $rel['rules'][0]);
                break;

            default:
                throw new Zend_Db_Table_Row_Exception($memberName . ' is not a row member');
        }

        return $this->_memberRows[$memberName];
    }

    /**
     *
     * @param string $memberName
     * @param Zend_Db_Table_Select $select
     * @return App_Db_Table_Rowset
     */
    public function loadMemberRowset($memberName, Zend_Db_Table_Select $select = null)
    {
        $rel = $this->getTable()->getRelation($memberName);
         
        switch($rel['relation'])
        {
            case 1: // has many
                $this->_memberRowsets[$memberName] =
                    $this->findDependentRowset($rel['refTableClass'], $rel['rules'][0], $select);
                break;

            case 2: // many to many
                $this->_memberRowsets[$memberName] = $this->findManyToManyRowset(
                    $rel['refTableClass'], $rel['intTableClass'],
                    $rel['rules'][0], $rel['rules'][1],
                    $select
                );
                break;
                 
            default:
                throw new Zend_Db_Table_Row_Exception($memberName . ' is not a rowset member');
        }

        return $this->_memberRowsets[$memberName];
    }
}