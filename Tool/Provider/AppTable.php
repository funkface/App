<?php

class App_Tool_Provider_AppTable extends Zend_Tool_Project_Provider_DbTable
{
	
	protected static $_isInitialized = false;
	
 	public function __construct()
    {
        // initialize the ZF Contexts (only once per php request)
        if (!self::$_isInitialized) {
            $contextRegistry = Zend_Tool_Project_Context_Repository::getInstance();
            $contextRegistry->addContextsFromDirectory(
                dirname(dirname(__FILE__)) . '/Context/', 'App_Tool_Context'
            );
            self::$_isInitialized = true;
        }
        
        parent::__construct();        
    }
	
    public static function createResource(
    	Zend_Tool_Project_Profile $profile, 
    	$appTableName, $actualTableName, $moduleName = null, $relations = null, $columns = null
    ){
        $profileSearchParams = array();

        if ($moduleName != null && is_string($moduleName)) {
            $profileSearchParams = array(
            	'modulesDirectory',
            	'moduleDirectory' => array('moduleName' => $moduleName)
            );
        }

        $profileSearchParams[] = 'modelsDirectory';
        
        $modelsDirectory = $profile->search($profileSearchParams);
        
        if (!($modelsDirectory instanceof Zend_Tool_Project_Profile_Resource)) {
            throw new Zend_Tool_Project_Provider_Exception(
                'A models directory was not found' .
                (($moduleName) ? ' for module ' . $moduleName . '.' : '.')
            );
        }
        
        if (!($appTableDirectory = $modelsDirectory->search('AppTableDirectory'))) {
            $appTableDirectory = $modelsDirectory->createResource('AppTableDirectory');
        }
        
        $appTableFile = $appTableDirectory->createResource(
        	'AppTableFile', 
        	array(
        		'appTableName' => $appTableName,
        		'actualTableName' => $actualTableName,
        		'relations' => $relations
        	)
        );
        
        $appTableRowFile = $modelsDirectory->createResource(
        	'AppTableRowFile', 
        	array(
        	   'appTableName' => $appTableName, 
        	   'relations' => $relations,
        	   'columns' => $columns
        	)
        );
        
        return array($appTableFile, $appTableRowFile);
    }
    
    public static function hasResource(
    	Zend_Tool_Project_Profile $profile, 
    	$appTableName, $moduleName = null
    )
    {
        $profileSearchParams = array();

        if ($moduleName != null && is_string($moduleName)) {
            $profileSearchParams = array(
            	'modulesDirectory',
            	'moduleDirectory' => array('moduleName' => $moduleName)
            );
        }

        $profileSearchParams[] = 'modelsDirectory';
        
        $modelsDirectory = $profile->search($profileSearchParams);
        
        if (!($modelsDirectory instanceof Zend_Tool_Project_Profile_Resource)
            || !($appTableDirectory = $modelsDirectory->search('AppTableDirectory'))) {
            return false;
        }
        
        $appTableFile = $appTableDirectory->search(array(
        	'AppTableFile' => array('appTableName' => $appTableName))
        );
        
        return ($appTableFile instanceof Zend_Tool_Project_Profile_Resource) ? true : false;
    }
      
    
    public function create($modelName, $actualTableName, $module = null, $forceOverwrite = false)
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);

        // Check that there is not a dash or underscore, return if doesnt match regex
        if (preg_match('#[_-]#', $modelName)) {
            throw new Zend_Tool_Project_Provider_Exception('Model names should be camel cased.');
        }
        
        $originalName = $modelName;
        $modelName = ucfirst($modelName);
        
        if ($actualTableName == '') {
            throw new Zend_Tool_Project_Provider_Exception(
            	'You must provide both the Model name as well as the actual db table\'s name.'
            );
        }
        
        if (self::hasResource($this->_loadedProfile, $modelName, $module)) {
            throw new Zend_Tool_Project_Provider_Exception(
            	'This project already has a AppTable named ' . $modelName
            );
        }

        // get request/response object
        $request = $this->_registry->getRequest();
        $response = $this->_registry->getResponse();
        
        // alert the user about inline converted names
        $tense = (($request->isPretend()) ? 'would be' : 'is');
        
        if ($modelName !== $originalName) {
            $response->appendContent(
                'Note: The canonical model name that ' . $tense
                    . ' used with other providers is "' . $modelName . '";'
                    . ' not "' . $originalName . '" as supplied',
                array('color' => array('yellow'))
                );
        }
        
        try {
            list($tableResource, $tableRowResource) =
            	self::createResource($this->_loadedProfile, $modelName, $actualTableName, $module);
            	
        } catch (Exception $e) {
            $response = $this->_registry->getResponse();
            $response->setException($e);
            return;
        }
        
        // do the creation
        if ($request->isPretend()) {
            $response->appendContent(
            	'Would create a Model_Table at '  . $tableResource->getContext()->getPath()
            );
            $response->appendContent(
            	'Would create a Model at '  . $tableRowResource->getContext()->getPath()
            );
        } else {
            $response->appendContent(
            	'Creating a Model_Table at ' . $tableResource->getContext()->getPath()
            );
            $response->appendContent(
            	'Creating a Model at ' . $tableRowResource->getContext()->getPath()
            );
            $tableResource->create();
            $tableRowResource->create();
            $this->_storeProfile();
        }
    }
    
    public function createFromDatabase($module = null, $forceOverwrite = false)
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);
        
        $bootstrapResource = $this->_loadedProfile->search('BootstrapFile');
        
        /* @var $zendApp Zend_Application */
        $zendApp = $bootstrapResource->getApplicationInstance();
        
        try {
            $zendApp->bootstrap('db');
        } catch (Zend_Application_Exception $e) {
            throw new Zend_Tool_Project_Provider_Exception(
            	'Db resource not available, you might need to configure a DbAdapter.'
            );
            return;
        }
        
        /* @var $db Zend_Db_Adapter_Abstract */
        $db = $zendApp->getBootstrap()->getResource('db');
        
        if(!$db instanceof App_Db_Adapter_Relatable){
        	throw new Zend_Tool_Project_Provider_Exception(
        		'A DB adapter implementing App_Db_Adapter_Relatable must be used for AppTable generation'
        	);
        }
        
        $tableResources = array();
        foreach ($db->listTables() as $actualTableName) {
            
            $appTableName = $this->_convertTableNameToClassName($actualTableName);
            
            if(
            	!$forceOverwrite && 
            	self::hasResource($this->_loadedProfile, $appTableName, $module)
            ){
                throw new Zend_Tool_Project_Provider_Exception(
                    'This AppTable resource already exists, if you wish to overwrite it, '
                    . 'pass the "forceOverwrite" flag to this provider.'
                );
            }
            
            $relations = $db->describeTableRelations($actualTableName);
            $columns = $db->describeTable($actualTableName);
            
            foreach($relations as &$relation)
            {
            	$relation['tableClass'] = $this->_convertTableNameToClassName($relation['table']);
            	$relation['refTableClass'] = $this->_convertTableNameToClassName($relation['refTable']);
            	
            	switch($relation['relation'])
            	{
            	    case 0: // FK on this table
            	        
            	        $relation['name'] = $this->_convertColumnNameToMemberName($relation['column']);
            	        $relation['rules'] = array($relation['name']);
            	        break;

            	    case 1: // PK on this table
            	        
            	        $name = $this->_convertColumnNameToClassName($relation['refColumn']);
                        $name = str_replace($relation['tableClass'], $relation['refTableClass'], $name, $count);
                        if($count < 1) $name .= $relation['refTableClass'];
                        $relation['name'] = lcfirst($name . 's');
                        
                        $relation['rules'] = array($this->_convertColumnNameToMemberName($relation['refColumn']));
                        
                        break;
          
            	    case 2: // Many to many

            	        $relation['intTableClass'] = $this->_convertTableNameToClassName($relation['intTable']);
                        $name = str_replace(
                            array($relation['tableClass'], $relation['refTableClass']), '', $relation['intTableClass']
                        );
                        $relation['name'] = lcfirst($name . $relation['refTableClass'] . 's');
      
                        $relation['rules'] = array(
                            $this->_convertColumnNameToMemberName($relation['intColumn']),
                            $this->_convertColumnNameToMemberName($relation['refIntColumn'])
                        ); 
            	}
            }
            
            $tableResources[] = self::createResource(
                $this->_loadedProfile,
                $appTableName,
                $actualTableName,
                $module,
                $relations,
                $columns
            );
        }
        
        if (count($tableResources) == 0) {
            $this->_registry->getResponse()->appendContent(
            	'There are no tables in the selected database to write.'
            );
        }
        
        $response = $this->_registry->getResponse();
        
        // do the creation
        if ($this->_registry->getRequest()->isPretend()) {

            foreach ($tableResources as $tableResource) {
                list($tableResource, $tableRowResource) = $tableResource;
                $response->appendContent(
                    'Would create a Model_Table at '  . $tableResource->getContext()->getPath()
                );
                $response->appendContent(
                    'Would create a Model at '  . $tableRowResource->getContext()->getPath()
                );
            }
            
        } else {

            foreach ($tableResources as $tableResource) {
                list($tableResource, $tableRowResource) = $tableResource;
                $response->appendContent(
                    'Creating a Model_Table at ' . $tableResource->getContext()->getPath()
                );
                $response->appendContent(
                    'Creating a Model at ' . $tableRowResource->getContext()->getPath()
                );
                $tableResource->create();
                $tableRowResource->create();
            }

            $this->_storeProfile();
        }
    }
    
    protected function _convertColumnNameToClassName($columnName)
    {
        if(preg_match('/^(\w+)_id$/', $columnName, $matches)) $columnName = $matches[1];
        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        return $filter->filter($columnName);
    }
    
    protected function _convertColumnNameToMemberName($columnName)
    {
        return lcfirst($this->_convertColumnNameToClassName($columnName));
    }
}