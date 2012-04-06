<?php

class ModelGenerator_BaseModel
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
        $table = new ModelGenerator_Table_Table($this->_options['tableName']);
        $className = $this->_getNamer()->formatClassName($this->_config->base->classname);

        $templates = array(
            'tags' => array(),
        );

        foreach ($this->_options['docblock'] as $tag => $value)
            $templates['tags'][] = array('name' => $tag, 'description' => $value);

        ////////////////////////////////////////////
        // create model base
        ////////////////////////////////////////////

        $methods = array();
        $properties = array();

        $tmp = array();
        foreach ($table->getProperties() as $property) {
            $tmp[] = array(
                'name' => 'property',
                'description' => $property['type'] . ' $'.$property['name'].' '.$property['desc']
            );

            $properties[] = new Zend_CodeGenerator_Php_Property(array(
                'name' => '_' . $property['name'],
                'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                    'tags' => array(
                        array('name' => 'var', 'description' => $property['type']),
                    ),
                )),
                'visibility' => 'private',
            ));

            // getters and setters
            if(false !== strpos($property['desc'], 'enum')) {
                $types = rtrim(str_replace(array('enum('), '', $property['desc']), ')');
                $types = explode(',', $types);
                $typesImploded = implode(', ', $types);

                $enumBody = 'in_array($'.$property['name'].', array('.$typesImploded.')) ? $'.$property['name'].' : '.$types[0];

                $body = '$'.$property['name'].' = ('.$property['type'].') $'.$property['name'].';' . PHP_EOL
                    .   '$this->_'.$property['name'].' = '.$enumBody.';' . PHP_EOL
                    .	'return $this;';
            } else {
                $body = '$this->_'.$property['name'].' = ('.$property['type'].') $'.$property['name'].';' . PHP_EOL
                    .	'return $this;';
            }

            $methods[] = new Zend_CodeGenerator_Php_Method(array(
                'name' => 'set'.$this->_getNamer()->formatMethodName($property['name']),
                'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                    'shortDescription' => 'Set '.$property['name'].' ('.$property['desc'].')',
                    'tags' => array(
                        new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                            'paramName' => $property['name'], 'dataType' => $property['type']
                        )),
                        array('name' => 'return', 'description' => $className),
                    ),
                )),
                'parameters' => array(
                    array('name' => $property['name']),
                ),
                'body' => $body,
            ));

            $methods[] = new Zend_CodeGenerator_Php_Method(array(
                'name' => 'get'.$this->_getNamer()->formatMethodName($property['name']),
                'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                    'shortDescription' => 'Get '.$property['name'].' ('.$property['desc'].')',
                    'tags' => array(
                        array('name' => 'return', 'description' => $property['type']),
                    ),
                )),
                'parameters' => array(),
                'body' =>
                'return $this->_'.$property['name'].';',
            ));
        }

        $modelBase = new Zend_CodeGenerator_Php_Class(array(
            'name' => $className,
            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => $className . PHP_EOL . '*DO NOT* edit this file.',
                'tags' => array_merge($tmp, $templates['tags']),
            )),
            //'extendedClass' => $table->getBaseExtension(),
            'methods' => $methods,
            'properties' => $properties,
        ));

        $modelBaseFile = new Zend_CodeGenerator_Php_File(array(
            'classes' => array($modelBase),
        ));

        return $modelBaseFile->generate();
    }
}