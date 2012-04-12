<?php

/**
 * This is a model
 * @author Jacek Kobus <jacekkobus.com>
 */
class ModelGenerator_Table_Table
{
    /**
     * @var Zend_Db_Table
     */

    private $table;

    /**
     * @var array
     */

    private $info;

    /**
     * @var ModelGenerator_Table_DependencyChecker
     */

    private $_dependencyChecker;

    /**
     * Create new table instance
     *
     * @param string $table
     */

    public function __construct($table)
    {
        $this->table              = new Zend_Db_Table($table);
        $this->_dependencyChecker = new ModelGenerator_Table_DependencyChecker();

        $analyser   = new ModelGenerator_Table_Analyser();
        $this->info = $analyser->analyzeTable($this->table);
    }

    /**
     * @return Zend_Db_Table
     */

    protected function getTable()
    {
        return $this->table;
    }

    /**
     * @return array
     */

    public function getParents()
    {
        return $this->_dependencyChecker->getParentsOf($this->getName());
    }

    /**
     * @return array
     */

    public function getChildren()
    {
        return $this->_dependencyChecker->getChildrenOf($this->getName());
    }

    /**
     * @return array
     */

    public function getDependentTables()
    {
        return $this->_dependencyChecker->getDependenciesFor($this->getName());
    }

    /**
     * Get table name
     * @return string
     */

    public function getName()
    {
        return $this->info['name'];
    }

    /**
     * @return array
     */

    public function getMetadata()
    {
        return $this->info['metadata'];
    }

    /**
     * @param array $column
     *
     * @return string
     */

    public function getColumnMetadata($column)
    {
        return $this->info['metadata'][$column];
    }

    /**
     * @return bool
     */

    public function hasForeignKeys()
    {
        if(!empty($this->info['foreign_keys'])) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */

    public function getForeignKeys()
    {
        if($this->hasForeignKeys()) {
            return $this->info['foreign_keys'];
        }
        return null;
    }

    /**
     * @return array
     */

    public function getColumns()
    {
        return $this->info['cols'];
    }

    /**
     * Get all avilable properties
     * @return array
     */
    public function getProperties()
    {
        $tmp = array();
        foreach($this->getColumns() as $id => $name) {
            $tmp[$name]['name'] = $name;
            $tmp[$name]['type'] = $this->getTypeFor($name);
            $tmp[$name]['desc'] = $this->getMysqlDatatype($name);
        }
        return $tmp;
    }

    /**
     * Get mysql datatype for specified key
     *
     * @param string $key
     *
     * @return string
     */

    public function getMysqlDatatype($key)
    {
        $meta = $this->getMetadata();
        return $meta[$key]['DATA_TYPE'];
    }

    /**
     * @return bool
     */

    public function hasUniqueKeys()
    {
        if(!empty($this->info['uniques'])) {
            return true;
        }
        return false;
    }

    /**
     * @param string $column
     *
     * @return bool
     */

    public function isUniqueKey($column)
    {
        if(isset($this->info['uniques'][$column])) {
            return true;
        }
        return false;
    }

    /**
     * @return array|null
     */

    public function getUniqueKeys()
    {
        if($this->hasUniqueKeys()) {
            return $this->info['uniques'];
        }
        return null;
    }

    /**
     * Get all primary keys
     * @return array
     */

    public function getPrimary()
    {
        return array_merge($this->info['primary'], array());
    }

    /**
     * Get all primary keys in array notation
     * @return string
     */

    public function getPrimaryAsString()
    {
        $primary = $this->getPrimary();
        return 'array(\'' . implode('\', \'', $primary) . '\')';
    }

    /**
     * Get all dependants
     * @return string
     */

    public function getDependantTables()
    {
        $tmp    = array();
        $tables = $this->_dependencyChecker->getChildrenOf($this->getName());
        if(is_array($tables)) {
            foreach($tables as $name => $smth) {
                $tmp[] = $this->getTableName($name);
            }
            return $tmp;
        } else {
            return array();
        }
    }

    /**
     * Get all dependants in array notation
     * @return string
     */

    public function getDependantAsString()
    {
        $tables = $this->getDependantTables();
        if(empty($tables)) {
            return 'array()';
        }
        $impl = implode('\', ' . PHP_EOL . '		\'', $tables);
        return 'array(' . PHP_EOL . '		\''
            . $impl . '\'' . PHP_EOL . '	)';
    }

    /**
     * Get column type
     *
     * @param string $column
     *
     * @return string|null
     */

    public function getTypeFor($column)
    {
        if(isset($this->info['phptypes'][$column])) {
            return $this->info['phptypes'][$column];
        }
        return null;
    }

    public function getCustomVar()
    {

    }

    public function getCustomVars()
    {

    }
}