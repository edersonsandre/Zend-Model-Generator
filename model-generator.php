<?php

/**
 * @author  Laurynas Karvelis <laurynas.karvelis@gmail.com>
 * @author  Explosive Brains Limited
 * @license http://sam.zoy.org/wtfpl/COPYING
 */

header('Content-type: text/plain; charset=utf-8');
@ob_end_flush();

// always use this dir as the main one
chdir(__DIR__);

set_include_path(implode(
        PATH_SEPARATOR,
        array(
             get_include_path(),
             './library',

             // add path to ZF library/ directory if needed
             '/www/ZendFramework-1.11.11-minimal/library'
        )
    )
);

if(!class_exists('Zend_Loader_Autoloader', false)) {
    require_once 'Zend/Loader/Autoloader.php';
}

Zend_Loader_Autoloader::getInstance()
    ->registerNamespace('ModelGenerator');

// prepare paths
define('APPLICATION_PATH', realpath('./../application'));

$configIniLocation      = __DIR__ . '/config.ini';
$applicationIniLocation = APPLICATION_PATH . '/configs/application.ini';

// init our generator
$generator = new ModelGenerator_Generator($configIniLocation, $applicationIniLocation);

// generate models :)
$generator->run();