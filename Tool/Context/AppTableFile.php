<?php
class 
App_Tool_Context_AppTableFile extends Zend_Tool_Project_Context_Zf_AbstractClassFile
{

    protected $_appTableName = null;
    
    protected $_actualTableName = null;
    
    /**
     * getName()
     *
     * @return string
     */
    public function getName()
    {
        return 'AppTableFile';
    }

    /**
     * init()
     *
     */
    public function init()
    {
        $this->_appTableName = $this->_resource->getAttribute('appTableName');
        $this->_actualTableName = $this->_resource->getAttribute('actualTableName');
        $this->_filesystemName = ucfirst($this->_appTableName) . '.php';
        parent::init();
    }
    
    public function getPersistentAttributes()
    {
        return array('appTableName' => $this->_appTableName);
    }

    public function getContents()
    {
        $className = $this->getFullClassName($this->_appTableName, 'Model_Table');
        $rowClassName = $this->getFullClassName($this->_appTableName, 'Model');
        
        $codeGenFile = new Zend_CodeGenerator_Php_File(array(
            'fileName' => $this->getPath(),
            'classes' => array(
                new Zend_CodeGenerator_Php_Class(array(
                    'name' => $className,
                    'extendedClass' => 'App_Db_Table',
                    'properties' => array(
                        new Zend_CodeGenerator_Php_Property(array(
                            'name' => '_name',
                            'visibility' => Zend_CodeGenerator_Php_Property::VISIBILITY_PROTECTED,
                            'defaultValue' => $this->_actualTableName
                            )),
                            new Zend_CodeGenerator_Php_Property(array(
                            'name' => '_rowClass',
                            'visibility' => Zend_CodeGenerator_Php_Property::VISIBILITY_PROTECTED,
                            'defaultValue' => $rowClassName
                            )),
                        ),
                        
                
                    ))
                )
            ));
        return $codeGenFile->generate();
    }
    
}