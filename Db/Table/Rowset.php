<?php
class App_Db_Table_Rowset extends Zend_Db_Table_Rowset
{
	public function save()
	{
		foreach($this as $row)
		{
			$row->save();
		}
	}
	
	/**
	 * @return bool
	 */
	public function isModified()
	{
		foreach($this as $row)
		{
			if($row->isModified()){
				return true;
			}
		}
		
		return false;
	}
}