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
    	$appTableName, $actualTableName, $moduleName = null
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
        	array('appTableName' => $appTableName, 'actualTableName' => $actualTableName)
        );
        
        $appTableRowFile = $modelsDirectory->createResource(
        	'AppTableRowFile', 
        	array('appTableName' => $appTableName)
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
      
    
    public function create($name, $actualTableName, $module = null, $forceOverwrite = false)
    {
        $this->_loadProfile(self::NO_PROFILE_THROW_EXCEPTION);

        // Check that there is not a dash or underscore, return if doesnt match regex
        if (preg_match('#[_-]#', $name)) {
            throw new Zend_Tool_Project_Provider_Exception('AppTable names should be camel cased.');
        }
        
        $originalName = $name;
        $name = ucfirst($name);
        
        if ($actualTableName == '') {
            throw new Zend_Tool_Project_Provider_Exception(
            	'You must provide both the AppTable name as well as the actual db table\'s name.'
            );
        }
        
        if (self::hasResource($this->_loadedProfile, $name, $module)) {
            throw new Zend_Tool_Project_Provider_Exception(
            	'This project already has a AppTable named ' . $name
            );
        }

        // get request/response object
        $request = $this->_registry->getRequest();
        $response = $this->_registry->getResponse();
        
        // alert the user about inline converted names
        $tense = (($request->isPretend()) ? 'would be' : 'is');
        
        if ($name !== $originalName) {
            $response->appendContent(
                'Note: The canonical model name that ' . $tense
                    . ' used with other providers is "' . $name . '";'
                    . ' not "' . $originalName . '" as supplied',
                array('color' => array('yellow'))
                );
        }
        
        try {
            list($tableResource, $tableRowResource) =
            	self::createResource($this->_loadedProfile, $name, $actualTableName, $module);
            	
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
            
            $tableResources[] = self::createResource(
                $this->_loadedProfile,
                $appTableName,
                $actualTableName,
                $module
                );
        }
        
        if (count($tableResources) == 0) {
            $this->_registry->getResponse()->appendContent(
            	'There are no tables in the selected database to write.'
            );
        }
        
        // do the creation
        if ($this->_registry->getRequest()->isPretend()) {

            foreach ($tableResources as $tableResource) {
                $this->_registry->getResponse()->appendContent(
                	'Would create an AppTable at '  . $tableResource->getContext()->getPath()
                );
            }

        } else {

            foreach ($tableResources as $tableResource) {
                $this->_registry->getResponse()->appendContent(
                	'Creating an AppTable at ' . $tableResource->getContext()->getPath()
                );
                $tableResource->create();
            }

            $this->_storeProfile();
        }
        
        
    }

}