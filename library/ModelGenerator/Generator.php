<?php

/**
 * Model Generator
 * @author Laurynas Karvelis <laurynas.karvelis@gmail.com>
 * @author Explosive Brains Limited
 * @license http://sam.zoy.org/wtfpl/COPYING
 */

class ModelGenerator_Generator
{
    /**
     * @var Zend_Config_Ini config.ini data
     */

    private $_config;

    /**
     * @var Zend_Config_Ini application.ini data
     */

    private $_appConfig;

    /**
     * @var Zend_Db_Adapter_Abstract Zend adapter
     */

    private $_adapter;

    /**
     * @var Zend_Log Zend logger
     */

    private $_logger;

    /**
     * @var bool True if generator can run, false if there were any issues loading configs and other resources
     */

    private $_canRun = true;

    /**
     * @var bool Is this script running in Windows platform
     */

    private $_isRunningInWindows = false;

    /**
     * @var array Available tables
     */

    private $_availableTables = array();

    /**
     * Object constructor
     *
     * Initialises our config.ini and application's application.ini
     *
     * @param string $configIniLocation
     * @param string $applicationIniLocation
     */

    public function __construct($configIniLocation, $applicationIniLocation)
    {
        try {
            $this->_config = new Zend_Config_Ini((string) $configIniLocation);
        } catch(Exception $e) {
            $this->log('Could not read config.ini, used path "' . ((string) $configIniLocation) . '"');
            $this->_canRun = false;
        }

        try {
            $this->_appConfig = new Zend_Config_Ini((string) $applicationIniLocation, 'development');
            $dbConfig = $this->_appConfig->resources->db;
        } catch(Exception $e) {
            $this->log('Could not read Your application\'s application.ini, used path "' . ((string) $configIniLocation) . '"');
            $this->_canRun = false;
        }

        if(empty($dbConfig->adapter) or empty($dbConfig->params)) {
            $this->_canRun = false;
        }

        $this->_adapter = Zend_Db::factory($dbConfig->adapter, $dbConfig->params);
        Zend_Db_Table::setDefaultAdapter($this->_adapter);

        $this->_isRunningInWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Gets Zend_Log instance (singleton)
     * @return Zend_Log
     */

    private function _getLogger()
    {
        if(!$this->_logger) {
            $this->_logger = new Zend_Log(new Zend_Log_Writer_Stream('php://output'));
        }

        return $this->_logger;
    }

    /**
     * Logs message to defined Zend_Log writer
     * @param string $message
     * @param int $priority
     * @param array|null $extras
     * @return ModelGenerator_Generator
     */

    public function log($message, $priority = Zend_Log::INFO, $extras = null)
    {
        $this->_getLogger()->log($message, $priority, $extras);
        return $this;
    }

    /**
     * Runs the generator for each defined table
     * @return bool
     */

    public function run()
    {
        if(false === $this->_canRun) {
            $this->log('Cannot run generator, fix issues defined above...');
            return false;
        }

        foreach($this->_config->table as $tableName => $moduleName) {
            if(false === $this->_tableExists($tableName)) {
                continue;
            }

            $configContainer = (!empty($moduleName))
                ? $this->_config->module->model
                : $this->_config->model;

            $namer = new ModelGenerator_Namer($moduleName, $tableName);
            $dirs = $this->_prepareDirectories($namer, $moduleName);

            $options = array(
                'tableName' => $tableName,
                'moduleName' => $moduleName,
                'config' => $configContainer,
                'docblock' => $this->_config->docblock,
            );

            // render and save base model class
            $baseModel = new ModelGenerator_BaseModel($options, $namer);

            $classBody = $baseModel->generate();
            $this->saveFile($classBody, $dirs['BaseModel'], $namer->formatFilename($configContainer->base->filename));

            // render and save base mapper class
            $baseMapper = new ModelGenerator_BaseMapper($options, $namer);

            $classBody = $baseMapper->generate();
            $this->saveFile($classBody, $dirs['BaseMapper'], $namer->formatFilename($configContainer->baseMapper->filename));

            // render and save mapper class
            $mapper = new ModelGenerator_Mapper($options, $namer);

            $classBody = $mapper->generate();
            $this->saveFile($classBody, $dirs['Mapper'], $namer->formatFilename($configContainer->mapper->filename), true);

            // render and save model class
            $model = new ModelGenerator_Model($options, $namer);

            $classBody = $model->generate();
            $this->saveFile($classBody, $dirs['Model'], $namer->formatFilename($configContainer->filename), true);
        }

        return true;
    }

    /**
     * Checks if table exists in database
     * @param string $table
     * @return bool
     */

    private function _tableExists($table)
    {
        if(empty($this->_availableTables)) {
            $this->_availableTables = $this->_adapter->listTables();
        }

        return in_array($table, $this->_availableTables);
    }

    /**
     * Create directories if needed for each model entity and mapper
     * @param ModelGenerator_Namer $namer
     * @param string $moduleName
     * @return array
     */

    private function _prepareDirectories($namer, $moduleName)
    {
        $dirsToCreate = array();

        $container = (!empty($moduleName))
            ? $this->_config->module->model
            : $this->_config->model;

        // try to create entities basepaths
        $dirsToCreate['BaseModel'] = $namer->formatDirectory($container->base->basepath);
        $dirsToCreate['BaseMapper'] = $namer->formatDirectory($container->baseMapper->basepath);
        $dirsToCreate['Model'] = $namer->formatDirectory($container->basepath);
        $dirsToCreate['Mapper'] = $namer->formatDirectory($container->mapper->basepath);

        $directoryPermissions = (!empty($this->_config->directory->permission))
            ? octdec($this->_config->directory->permission)
            : 0755;

        // create directories if does not exist
        foreach($dirsToCreate as $directoryType => $dir) {
            if(!is_dir($dir)) {
                mkdir($dir, $directoryPermissions, true);
            }
        }

        // return active directories where to save our model
        return $dirsToCreate;
    }

    /**
     * Writes contents to file
     * @param string $contents
     * @param string $dir
     * @param string $filename
     * @param bool $checkIfDoesNotExist
     */

    private function saveFile($contents, $dir, $filename, $checkIfDoesNotExist = false)
    {
        $destination = rtrim($dir, '/\\') . '/' . $filename;

        if(true === $checkIfDoesNotExist) {
            if(file_exists($destination)) {
                return;
            }
        }

        file_put_contents($destination, $contents);
        chmod($destination, (!empty($this->_config->file->permission))
            ? octdec($this->_config->file->permission)
            : 0644
        );
    }
}