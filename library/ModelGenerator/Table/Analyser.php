<?php

/**
 * Analyze tables and dependencies
 * @author Jacek Kobus <kobus.jacek@gmail.com>
 * @version $Id: Analyser.php 22 2011-11-22 11:16:42Z kobus.jacek@gmail.com $
 */

class ModelGenerator_Table_Analyser
{

    private $_dependencyChecker;

	/**
	 * Create new analyzer
	 * @param Zend_Db_Table_Abstract $table
	 */
	public function __construct()
	{

	}
	
	/**
	 * Start analyzer
	 */
	public function analyzeTable(Zend_Db_Table_Abstract $table)
	{
		$info = $table->info();

		$info['uniques'] = array();
		unset(
			$info['sequence'],
			//$info['cols'],
			$info['schema'],
			$info['rowClass'],
			$info['rowsetClass'],
			$info['dependentTables'],
			$info['referenceMap']
		);
		
		$adapter = $table->getAdapter();
		
		foreach ($info['metadata'] as $property => $details){
			// match php types
			$info['phptypes'][$property] =
				$this->convertMysqlTypeToPhp($details['DATA_TYPE']);
				
			// find uniques
			$tmp = $adapter->fetchRow('DESCRIBE `'.$info['name'].'` `'.$property.'`;');
			if(!empty($tmp['Key']) && $tmp['Key'] != 'MUL'){
				$info['uniques'][$property] = $property;
			}
		}
		
		// get f-keys
		$result = $adapter->fetchAll('SHOW CREATE TABLE `' . $info['name'].'`');
		$query = $result[0]['Create Table'];
		$lines = explode("\n", $query);
		$tblinfo = array();
		$keys = array();
		foreach ($lines as $line) {
			preg_match('/^\s*CONSTRAINT `(\w+)` FOREIGN KEY \(`(\w+)`\) REFERENCES `(\w+)` \(`(\w+)`\)/',$line, $tblinfo);
			if (sizeof($tblinfo) > 0) {
				$keys[] = $tmp = array(
					'key' 		=> $tblinfo[1],
					'column' 	=> $tblinfo[2],
					'fk_table' 	=> $tblinfo[3],
					'fk_column' => $tblinfo[4]
				);
				
				$this->getDependencyChecker()->isChild(
					$info['name'],
					$tmp['column'],
					$tmp['key'],
					$tmp['fk_table'],
					$tmp['fk_column']);
			}
		}
		$info['foreign_keys'] = $keys;
		return $info;
	}
	
	/**
	 * @return Generator_Dependencies
	 */
	public function getDependencyChecker()
	{
        if(!$this->_dependencyChecker) {
            $this->_dependencyChecker = new ModelGenerator_Table_DependencyChecker();
        }

        return $this->_dependencyChecker;
	}
	
	/**
	 * map mysql data types to php data types
	 * @param string $mysqlType
	 * @return string
	 */
	protected function convertMysqlTypeToPhp($mysqlType) {
		
		$type = 'string';
		
		// integers
		if(preg_match('#^(.*)int(.*)$#', $mysqlType)){
			$type = 'int';
		}
		
		if(preg_match('#^(.*)float(.*)$#', $mysqlType)){
			$type = 'float';
		}

        if(preg_match('#^(.*)double(.*)$#', $mysqlType)){
            $type = 'double';
        }

		return $type;
	}
	
}