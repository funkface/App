<?php

class App_Tool_Context_AppTableRowFile extends Zend_Tool_Project_Context_Zf_AbstractClassFile
{

    protected $_appTableName = null;
    
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
        parent::init();
    }
    
    public function getPersistentAttributes()
    {
        return array('appTableName' => $this->_appTableName);
    }

    public function getContents()
    {
        $className = $this->getFullClassName($this->_appTableName, 'Model');
        
        $codeGenFile = new Zend_CodeGenerator_Php_File(array(
            'fileName' => $this->getPath(),
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