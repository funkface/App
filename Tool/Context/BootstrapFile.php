<?php
class App_Tool_Context_BootstrapFile extends Zend_Tool_Project_Context_Zf_BootstrapFile
{
    protected $_applicationEnvironment = 'tool';
    
    public function getApplicationInstance()
    {
        if ($this->_applicationInstance == null) {
            if ($this->_applicationConfigFile->getContext()->exists()) {
                define('APPLICATION_PATH', $this->_applicationDirectory->getPath());
                $applicationOptions = array();
                $applicationOptions['config'] = $this->_applicationConfigFile->getPath();
    
                $this->_applicationInstance = new Zend_Application(
                    $this->_applicationEnvironment,
                    $applicationOptions
                );
            }
        }
        
        return $this->_applicationInstance;
    }
}