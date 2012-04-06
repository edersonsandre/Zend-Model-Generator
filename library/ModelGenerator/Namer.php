<?php

/**
 * Naming class
 * @author Laurynas Karvelis <laurynas.karvelis@gmail.com>
 * @author Explosive Brains Limited
 * @license http://sam.zoy.org/wtfpl/COPYING
 */

class ModelGenerator_Namer
{
    /**
     * @var string current module name
     */

    private $_moduleName;

    /**
     * @var string current table
     */

    private $_tableName;

    /**
     * Constructor
     *
     * Initialises module and table name
     *
     * @param $moduleName
     * @param $tableName
     */

    public function __construct($moduleName, $tableName)
    {
        $this->_moduleName = $moduleName;
        $this->_tableName = $tableName;
    }

    /**
     * Format underscored string to CamelCased string
     * @param string $string
     * @return string
     */

    public function formatUnderscoreToCamel($string)
    {
        $tmp = explode('_', $string);

        foreach ($tmp as &$id) {
            $id = ucfirst($id);
        }

        $string = implode('', $tmp);

        return $string;
    }

    /**
     * Format class name
     * @param string $pattern
     * @return string
     */

    public function formatClassName($pattern)
    {
        return str_ireplace(
            array(
                '<Module>',
                '<Table>',
            ),
            array(
                ucfirst($this->_moduleName),
                ucfirst($this->formatUnderscoreToCamel($this->_tableName)),
            ),
            $pattern
        );
    }

    /**
     * Format directory name
     * @param string $pattern
     * @return string
     */

    public function formatDirectory($pattern)
    {
        return str_ireplace(
            array(
                '<application>',
                '<module>',
                '<Table>',
            ), array(
                APPLICATION_PATH,
                $this->_moduleName,
                ucfirst($this->formatUnderscoreToCamel($this->_tableName)),
            ),
            $pattern
        );
    }

    /**
     * Format file name
     * @param string $pattern
     * @return string
     */

    public function formatFilename($pattern)
    {
        return str_ireplace(
            array(
                '<Module>',
                '<Table>',
            ), array(
                ucfirst($this->formatUnderscoreToCamel($this->_moduleName)),
                ucfirst($this->formatUnderscoreToCamel($this->_tableName)),
            ),
            $pattern
        );
    }

    /**
     * Formats method name
     * @param string $string
     * @return string
     */

    public function formatMethodName($string)
    {
        return ucfirst($this->formatUnderscoreToCamel($string));
    }
}