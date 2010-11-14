<?php
class 
App_Tool_Context_AppTableFile extends Zend_Tool_Project_Context_Zf_AbstractClassFile
{

    protected $_appTableName = null;
    protected $_actualTableName = null;
    protected $_relations = array();
    
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
        $this->_relations = $this->_resource->getAttribute('relations');
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
        
        $properties = array(
			new Zend_CodeGenerator_Php_Property(array(
				'name' => '_name',
				'visibility' => Zend_CodeGenerator_Php_Property::VISIBILITY_PROTECTED,
				'defaultValue' => $this->_actualTableName
			)),
			new Zend_CodeGenerator_Php_Property(array(
				'name' => '_rowClass',
				'visibility' => Zend_CodeGenerator_Php_Property::VISIBILITY_PROTECTED,
				'defaultValue' => $rowClassName
			))
		);
			
		$refMap = $this->_prepareReferenceMap();
		if(!empty($refMap)){
			$properties[] = new Zend_CodeGenerator_Php_Property(array(
				'name' => '_referenceMap',
				'visibility' => Zend_CodeGenerator_Php_Property::VISIBILITY_PROTECTED,
				'defaultValue' => $refMap
			));
		}
        
        $codeGenFile = new Zend_CodeGenerator_Php_File(array(
            'fileName' => $this->getPath(),
            'classes' => array(
                new Zend_CodeGenerator_Php_Class(array(
                    'name' => $className,
                    'extendedClass' => 'App_Db_Table',
                    'properties' => $properties
                ))
            )
        ));
        
        return $codeGenFile->generate();
    }
    
    protected function _prepareReferenceMap()
    {
    	$refMap = array();
    	
    	foreach($this->_relations as $relation)
    	{
    		if($this->_appTableName == $relation['tableClass'])
    		{
    			$refMap[$relation['name']] = array(
    				'columns' => array($relation['column']),
    				'refTableClass' => $relation['refTableClass'],
    				'refColumns' => array($relation['refColumn'])
    			);
    		}
    	}
    	
    	return $refMap;
    }
    
}