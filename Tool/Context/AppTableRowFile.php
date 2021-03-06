<?php

class App_Tool_Context_AppTableRowFile extends Zend_Tool_Project_Context_Zf_AbstractClassFile
{

    protected $_appTableName = null;
    protected $_relations = array();
    protected $_columns = array();
    
    /**
     * getName()
     *
     * @return string
     */
    public function getName()
    {
        return 'AppTableRowFile';
    }

    /**
     * init()
     *
     */
    public function init()
    {
        $this->_appTableName = $this->_resource->getAttribute('appTableName');
        $this->_filesystemName = ucfirst($this->_appTableName) . '.php';
        $this->_relations = $this->_resource->getAttribute('relations');
        $this->_columns = $this->_resource->getAttribute('columns');
        parent::init();
    }
    
    public function getPersistentAttributes()
    {
        return array('appTableName' => $this->_appTableName);
    }

    public function getContents()
    {
        $className = $this->getFullClassName($this->_appTableName, 'Model');
        
        $docblock = new Zend_CodeGenerator_Php_Docblock();
        foreach($this->_columns as $column)
        {
            $description = 'string';
            if($column['NULLABLE']) $description .= '|null';
            $description .= ' $' . $column['COLUMN_NAME'] . ' mapped to DB column of type ' . $column['DATA_TYPE'];
            
            $length = array();
            if($column['LENGTH']) $length[] = $column['LENGTH'];
            if($column['PRECISION']) $length[] = $column['PRECISION'];
            if($column['SCALE']) $length[] = $column['SCALE'];
            if(!empty($length)) $description .= '(' . implode(',', $length) . ')';
            
            $docblock->setTag(new Zend_CodeGenerator_Php_Docblock_Tag(array(
                'name' => 'property',
                'description' => $description
            )));
        }
        
        foreach($this->_relations as $relation)
        {
            switch($relation['relation'])
            {
                case 0:
                    $description = $relation['refTableClass'];
                    if($this->_columns[$relation['column']]['NULLABLE']) $description .= '|null';
                    $description .= ' $' . $relation['name'] . 
                        ' maps many ' . $relation['tableClass'] . ' to 1 ' . $relation['refTableClass'];
                    break;
                    
                case 1:
                    $description = 'App_Db_Table_Rowset $' . $relation['name'] . 
                        ' maps 1 ' . $relation['tableClass'] . ' to many ' . $relation['refTableClass'];
                    break;
                    
                case 2:
                    
                    $description = 'App_Db_Table_Rowset $' . $relation['name'] .
                        ' maps many ' . $relation['tableClass'] . ' to many ' . $relation['refTableClass'] .
                        ' through ' . $relation['intTableClass'];
                    break;
            }
            
            $docblock->setTag(new Zend_CodeGenerator_Php_Docblock_Tag(array(
                'name' => 'property',
                'description' => $description
            )));
            
        }
        
        $codeGenFile = new Zend_CodeGenerator_Php_File(array(
            'fileName' => $this->getPath(),
            'docblock' => $docblock,
            'classes' => array(
                new Zend_CodeGenerator_Php_Class(array(
                    'name' => $className,
                    'extendedClass' => 'App_Db_Table_Row'
                ))
            )
        ));
        return $codeGenFile->generate();
    }
    
}