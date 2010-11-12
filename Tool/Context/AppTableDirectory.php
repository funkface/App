<?php
class App_Tool_Context_AppTableDirectory extends Zend_Tool_Project_Context_Filesystem_Directory
{
	/**
     * @var string
     */
    protected $_filesystemName = 'Table';

    /**
     * getName()
     *
     * @return string
     */
    public function getName()
    {
        return 'AppTableDirectory';
    }
}