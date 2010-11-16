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
        #c.UPDATE_RULE, c.DELETE_RULE,
        #LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS c 
        #ON a.CONSTRAINT_SCHEMA = c.CONSTRAINT_SCHEMA
        #AND a.CONSTRAINT_NAME = c.CONSTRAINT_NAME
        
        SELECT 
        TABLE_NAME, COLUMN_NAME, 
        REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME,
        0 AS RELATION, NULL AS INTERSECTION_TABLE_NAME
        
        FROM information_schema.KEY_COLUMN_USAGE
        
        WHERE TABLE_SCHEMA = 'project'
        AND REFERENCED_TABLE_SCHEMA = 'project'
        AND REFERENCED_COLUMN_NAME IS NOT NULL

        UNION SELECT
        REFERENCED_TABLE_NAME AS TABLE_NAME, REFERENCED_COLUMN_NAME AS COLUMN_NAME, 
        TABLE_NAME AS REFERENCED_TABLE_NAME, COLUMN_NAME AS REFERENCED_COLUMN_NAME,
        1 AS RELATION, NULL AS INTERSECTION_TABLE_NAME
        
        FROM information_schema.KEY_COLUMN_USAGE
        
        WHERE TABLE_SCHEMA = 'project'
        AND REFERENCED_TABLE_SCHEMA = 'project'
        AND REFERENCED_COLUMN_NAME IS NOT NULL

        UNION SELECT 
        a.REFERENCED_TABLE_NAME AS TABLE_NAME, a.REFERENCED_COLUMN_NAME AS COLUMN_NAME,
        d.REFERENCED_TABLE_NAME, d.REFERENCED_COLUMN_NAME,
        b.ORDINAL_POSITION AS RELATION, a.TABLE_NAME AS INTERSECTION_TABLE_NAME

        FROM information_schema.KEY_COLUMN_USAGE a
        
        INNER JOIN information_schema.KEY_COLUMN_USAGE b
        ON a.TABLE_SCHEMA = b.TABLE_SCHEMA
        AND a.TABLE_NAME = b.TABLE_NAME
        AND b.CONSTRAINT_NAME = 'PRIMARY'
        AND b.ORDINAL_POSITION = 2
        
        LEFT JOIN information_schema.KEY_COLUMN_USAGE d
        ON a.TABLE_SCHEMA = d.TABLE_SCHEMA
        AND a.TABLE_NAME = d.TABLE_NAME
        AND d.REFERENCED_COLUMN_NAME IS NOT NULL
        AND b.ORDINAL_POSITION = 2
        AND a.COLUMN_NAME != d.COLUMN_NAME
        
        WHERE a.TABLE_SCHEMA = 'project'
        AND a.REFERENCED_TABLE_SCHEMA = 'project'
        AND a.REFERENCED_COLUMN_NAME IS NOT NULL
        
        ORDER BY TABLE_NAME, RELATION
        */
        
        $qSchema = $this->quote($schemaName); 
        $qTable = $this->quote($tableName);
        
        $sql = 'SELECT 
        TABLE_NAME, COLUMN_NAME, 
        REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME,
        0 AS RELATION, NULL AS INTERSECTION_TABLE_NAME,
        NULL AS INTERSECTION_COLUMN, NULL AS REFERENCED_INTERSECTION_COLUMN
        
        FROM information_schema.KEY_COLUMN_USAGE
        
        WHERE TABLE_SCHEMA = ' . $qSchema . '
        AND REFERENCED_TABLE_SCHEMA = ' . $qSchema . '
        AND TABLE_NAME = ' . $qTable . '
        AND REFERENCED_COLUMN_NAME IS NOT NULL

        UNION SELECT
        REFERENCED_TABLE_NAME AS TABLE_NAME, REFERENCED_COLUMN_NAME AS COLUMN_NAME, 
        TABLE_NAME AS REFERENCED_TABLE_NAME, COLUMN_NAME AS REFERENCED_COLUMN_NAME,
        1 AS RELATION, NULL AS INTERSECTION_TABLE_NAME,
        NULL AS INTERSECTION_COLUMN, NULL AS REFERENCED_INTERSECTION_COLUMN
        
        FROM information_schema.KEY_COLUMN_USAGE
        
        WHERE TABLE_SCHEMA = ' . $qSchema . '
        AND REFERENCED_TABLE_SCHEMA = ' . $qSchema . '
        AND REFERENCED_TABLE_NAME = ' . $qTable . '
        AND REFERENCED_COLUMN_NAME IS NOT NULL

        UNION SELECT 
        a.REFERENCED_TABLE_NAME AS TABLE_NAME, a.REFERENCED_COLUMN_NAME AS COLUMN_NAME,
        d.REFERENCED_TABLE_NAME, d.REFERENCED_COLUMN_NAME,
        b.ORDINAL_POSITION AS RELATION, a.TABLE_NAME AS INTERSECTION_TABLE_NAME,
        a.COLUMN_NAME AS INTERSECTION_COLUMN, d.COLUMN_NAME AS REFERENCED_INTERSECTION_COLUMN

        FROM information_schema.KEY_COLUMN_USAGE a
        
        INNER JOIN information_schema.KEY_COLUMN_USAGE b
        ON a.TABLE_SCHEMA = b.TABLE_SCHEMA
        AND a.TABLE_NAME = b.TABLE_NAME
        AND b.CONSTRAINT_NAME = \'PRIMARY\'
        AND b.ORDINAL_POSITION = 2
        
        LEFT JOIN information_schema.KEY_COLUMN_USAGE d
        ON a.TABLE_SCHEMA = d.TABLE_SCHEMA
        AND a.TABLE_NAME = d.TABLE_NAME
        AND d.REFERENCED_COLUMN_NAME IS NOT NULL
        AND b.ORDINAL_POSITION = 2
        AND a.COLUMN_NAME != d.COLUMN_NAME
        
        WHERE a.TABLE_SCHEMA = ' . $qSchema . '
        AND a.REFERENCED_TABLE_SCHEMA = ' . $qSchema . '
        AND a.REFERENCED_TABLE_NAME = ' . $qTable . '
        AND a.REFERENCED_COLUMN_NAME IS NOT NULL
        
        ORDER BY TABLE_NAME, RELATION';
        
        $stmt = $this->query($sql);

        // Use FETCH_NUM so we are not dependent on the CASE attribute of the PDO connection
        $result = $stmt->fetchAll(Zend_Db::FETCH_NUM);
        $desc = array();
        foreach($result as $row)
        {
        	$desc[] = array(
        		'table' => $row[0],
        		'column' => $row[1],
        		'refTable' => $row[2],
        		'refColumn' => $row[3],
        		'relation' => (int)$row[4],
                'intTable' => $row[5],
        	    'intColumn' => $row[6],
                'refIntColumn' => $row[7]
        	);
        }
        
        return $desc;
	}
	
}