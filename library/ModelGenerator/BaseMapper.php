<?php

/**
 * BaseMapper generator
 * @author Laurynas Karvelis <laurynas.karvelis@gmail.com>
 * @author Explosive Brains Limited
 * @license http://sam.zoy.org/wtfpl/COPYING
 */

class ModelGenerator_BaseMapper
{
    /**
     * @var array options
     */

    private $_options = array();

    /**
     * @var mixed config.ini configs
     */

    private $_config;

    /**
     * @var ModelGenerator_Namer namer
     */

    private $_namer;

    /**
     * Constructor
     *
     * Initialises options and assigns ModelGenerator_Namer to itself
     *
     * @param array $options
     * @param ModelGenerator_Namer $namer
     */

    public function __construct(array $options, ModelGenerator_Namer $namer)
    {
        $this->_options = $options;
        $this->_config = $options['config'];
        $this->_namer = $namer;
    }

    /**
     * Gets the ModelGenerator_Namer object
     * @return ModelGenerator_Namer
     */

    private function _getNamer()
    {
        return $this->_namer;
    }

    /**
     * Generate class
     * @return string generated source code
     */

    public function generate()
    {
        $table = new ModelGenerator_Table_Table($this->_options['tableName']);
        $className = $this->_getNamer()->formatClassName($this->_config->baseMapper->classname);

        $templates = array(
            'tags' => array(),
        );

        foreach ($this->_options['docblock'] as $tag => $value)
            $templates['tags'][] = array('name' => $tag, 'description' => $value);

        $methods = array();
        $tableReferences = array();

        foreach ($table->getUniqueKeys() as $key){

            $methods[] = new Zend_CodeGenerator_Php_Method(array(
                'name' => 'findBy'.$this->_getNamer()->formatMethodName($key),
                'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                    'tags' => array(
                        new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                            'paramName' => $key, 'dataType' => 'mixed',
                        )),
                        array('name' => 'return', 'description' => $this->_getNamer()->formatClassName($this->_config->classname)),
                    ),
                )),
                'parameters' => array(
                    array('name' => $key),
                ),
                'body' => 'return $this->findOne(array(\''.$key.' = ?\' => $' . $key . '));',
            ));
        }

        $modelTableBase = new Zend_CodeGenerator_Php_Class(array(
            'name' => $className,
            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => $className . PHP_EOL . '*DO NOT* edit this file.',
                'tags' => array_merge($templates['tags']),
            )),
            'extendedClass' => 'Zend_Db_Table_Abstract',
            'properties' => array(

                array('name' => '_name', 'visiblity' => 'protected', 'defaultValue' => $table->getName()),
                array('name' => '_primary', 'visiblity' => 'protected', 'defaultValue' => $table->getPrimary()),
                array('name' => '_dependantTables', 'visiblity' => 'protected', 'defaultValue' => $table->getDependantTables()),
            ),
            'methods' => $methods,
        ));

        $modelTableBaseFile = new Zend_CodeGenerator_Php_File(array(
            'classes' => array($modelTableBase),
        ));

        return $modelTableBaseFile->generate();
    }
}