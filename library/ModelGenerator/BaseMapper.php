<?php

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
     * Generate and save table models
     * @return string generated source code
     */

    public function generate()
    {
        $className = $this->_getNamer()->formatClassName($this->_config->baseMapper->classname);

        $templates = array(
            'tags' => array(),
        );

        foreach ($this->_options['docblock'] as $tag => $value)
            $templates['tags'][] = array('name' => $tag, 'description' => $value);



        $modelBase = new Zend_CodeGenerator_Php_Class(array(
            'name' => $className,
            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => $className . PHP_EOL . '*DO NOT* edit this file.',
                'tags' => $templates['tags'],
            )),
        ));

        $modelBaseFile = new Zend_CodeGenerator_Php_File(array(
            'classes' => array($modelBase),
        ));

        return $modelBaseFile->generate();
    }
}