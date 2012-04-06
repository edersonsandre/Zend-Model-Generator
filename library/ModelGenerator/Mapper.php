<?php

/**
 * Mapper generator
 * @author Laurynas Karvelis <laurynas.karvelis@gmail.com>
 * @author Explosive Brains Limited
 * @license http://sam.zoy.org/wtfpl/COPYING
 */

class ModelGenerator_Mapper
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

        $className = $this->_getNamer()->formatClassName($this->_config->mapper->classname);
        $baseMapperClassName = $this->_getNamer()->formatClassName($this->_config->baseMapper->classname);

        $templates = array(
            'tags' => array(),
        );

        foreach ($this->_options['docblock'] as $tag => $value)
            $templates['tags'][] = array('name' => $tag, 'description' => $value);

        $mapperTable = new Zend_CodeGenerator_Php_Class(array(
            'name' => $className,
            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => $className . PHP_EOL . 'Put your custom methods in this file.',
                'tags' => array_merge($templates['tags']),
            )),
            'extendedClass' => $baseMapperClassName,
        ));

        $mapperTableFile = new Zend_CodeGenerator_Php_File(array(
            'classes' => array($mapperTable),
        ));

        return $mapperTableFile->generate();
    }
}