<?php
class App_Db_Adapter_Pdo_Mysql 
extends Zend_Db_Adapter_Pdo_Mysql 
implements App_Db_Adapter_Relatable
{
	public function describeTableRelations($tableName, $schemaName = null)
	{
		if(!$schemaName){
		 	$schemaName = $this->_config['dbname'];
        }
        
        /*

		SELECT a.TABLE_NAME, a.COLUMN_NAME, a.REFERENCED_TABLE_NAME, a.REFERENCED_COLUMN_NAME,
		b.ORDINAL_POSITION AS PIVOT, c.UPDATE_RULE, c.DELETE_RULE
		
        FROM information_schema.KEY_COLUMN_USAGE a
        
        LEFT JOIN information_schema.KEY_COLUMN_USAGE b
        ON a.TABLE_SCHEMA = b.TABLE_SCHEMA
        AND a.TABLE_NAME = b.TABLE_NAME
        AND b.CONSTRAINT_NAME = 'PRIMARY'
        AND b.ORDINAL_POSITION = 2
        
        LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS c 
        ON a.CONSTRAINT_SCHEMA = c.CONSTRAINT_SCHEMA
        AND a.CONSTRAINT_NAME = c.CONSTRAINT_NAME
        
        WHERE a.TABLE_SCHEMA = 'project'
        AND a.REFERENCED_TABLE_SCHEMA = 'project'
        AND a.REFERENCED_COLUMN_NAME IS NOT NULL
        
        */
        
        $sql = "SELECT a.COLUMN_NAME, a.REFERENCED_TABLE_NAME, a.REFERENCED_COLUMN_NAME,
		b.ORDINAL_POSITION AS INTERSECTION, c.UPDATE_RULE, c.DELETE_RULE
		
        FROM information_schema.KEY_COLUMN_USAGE a
        
        LEFT JOIN information_schema.KEY_COLUMN_USAGE b
        ON a.TABLE_SCHEMA = b.TABLE_SCHEMA
        AND a.TABLE_NAME = b.TABLE_NAME
        AND b.CONSTRAINT_NAME = 'PRIMARY'
        AND b.ORDINAL_POSITION = 2
        
        LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS c 
        ON a.CONSTRAINT_SCHEMA = c.CONSTRAINT_SCHEMA
        AND a.CONSTRAINT_NAME = c.CONSTRAINT_NAME
        
        WHERE a.TABLE_SCHEMA = " . $this->quote($schemaName) . '
        AND a.REFERENCED_TABLE_SCHEMA = ' . $this->quote($schemaName) . '
        AND (a.TABLE_NAME = ' . $this->quote($tableName) . '
        OR a.REFERENCED_TABLE_NAME = ' . $this->quote($tableName) . ')
        AND a.REFERENCED_COLUMN_NAME IS NOT NULL';
        
        $stmt = $this->query($sql);

        // Use FETCH_NUM so we are not dependent on the CASE attribute of the PDO connection
        $result = $stmt->fetchAll(Zend_Db::FETCH_NUM);
        $desc = array();
        foreach($result as $row)
        {
        	$desc[] = array(
        		'column' => $row[0],
        		'refTable' => $row[1],
        		'refColumn' => $row[2],
        		'intersection' => (bool)$row[3],
        		'onUpdate' => $row[4],
        		'onDelete' => $row[5]
        	);
        }
        
        return $desc;
	}
	
}