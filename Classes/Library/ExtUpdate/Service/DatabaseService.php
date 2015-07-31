<?php
namespace Innologi\Decospublisher7\Library\ExtUpdate\Service;
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException;
/**
 * Ext Update Database Service
 *
 * Provides several database methods for common use-cases in ext-update context
 *
 * @package InnologiLibs
 * @subpackage ExtUpdate
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DatabaseService implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * Language labels => messages
	 *
	 * @var array
	 */
	protected $lang = array(
		'noData' => 'No \'<code>%1$s</code>\' records to migrate.',
		'noUniqueData' => 'No \'<code>%1$s</code>\' records to create.',
		'sqlError' => 'The following database query produced an unknown error: <pre>%1$s</pre>',
		'target>source' => 'Automatic migration completely failed due to uid-reference-overlapping. You will have to start over completely by reverting a database/table backup, or remove all data and re-import all Decos data manually. Possible reason: imports were updated or TCA records were created before migration was complete. (Table: %1$s, Property: %2$s, Source: %3$s, Target: %4$s)',
		'unexpectedNoMatch' => 'The following values for \'<code>%1$s</code>\' should match but do not: <code>%2$s</code>'
	);

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		$this->databaseConnection->store_lastBuiltQuery = TRUE;
		// @TODO ___utilize $this->databaseConnection->sql_error() ?
	}

	/**
	 * Migrates table data from one table to another.
	 *
	 * Note that this only works with tables using an AUTO_INCREMENT uid,
	 * as we rely on this property and insert_id
	 *
	 * @param string $sourceTable
	 * @param string $targetTable
	 * @param array $propertyMap Contains sourceProperty => targetProperty mappings
	 * @param integer $limitRecords
	 * @param array &$uidMap Reference for storing sourceUid => targetUid mappings
	 * @throws Exception\NoData Nothing to migrate
	 * @return integer Affected record count
	 */
	public function migrateTableData($sourceTable, $targetTable, array $propertyMap, $limitRecords = 5000, array &$uidMap = array()) {
		// select all data rows to migrate, set uid as keys
		$toMigrate = $this->selectTableRecords($sourceTable, '', '*', $limitRecords);
		$count = count($toMigrate);

		if ($count <= 0) {
			throw new Exception\NoData(
				sprintf($this->lang['noData'], $sourceTable)
			);
		}

		// translate rows to insertable data according to $propertyMap
		$toInsert = $this->translatePropertiesOfRows($toMigrate, $propertyMap);

		// insert, note that we use $propertyMap to define the $fields parameter
		$this->insertTableRecords($targetTable, $propertyMap, $toInsert);
		// retrieve new uid's and combine them with original uid's
		$startUid = $this->databaseConnection->sql_insert_id();
		// affected_rows is not reliable when debugging (not sure if xdebug issue), so I'm using $count instead
		$endUid = $startUid + $count;
		$targetUidArray = array();
		for ($i = $startUid; $i < $endUid; $i++) {
			$targetUidArray[] = $i;
		}

		$sourceUidArray = array_keys($toMigrate);
		$uidMap = array_combine($sourceUidArray, $targetUidArray);

		// remove the old data
		if ($count < $limitRecords) {
			// if there are definitely no more rows to convert,
			// then truncate is quicker and cleaner than delete
			$this->databaseConnection->exec_TRUNCATEquery($sourceTable);
		} else {
			$this->deleteTableRecords(
				$sourceTable,
				'uid IN (\'' . join('\',\'', $sourceUidArray) . '\')'
			);
		}

		return $count;
	}

	/**
	 * Migrates MM table from one table to another.
	 *
	 * Variation of migrateTableData()
	 *
	 * @param string $sourceTable
	 * @param string $targetTable
	 * @param array $propertyMap Contains sourceProperty => targetProperty mappings
	 * @param integer $limitRecords
	 * @throws Exception\NoData Nothing to migrate
	 * @return integer Affected record count
	 */
	public function migrateMmTable($sourceTable, $targetTable, array $propertyMap, $limitRecords = 5000) {
		// select all data rows to migrate
		$toMigrate = $this->databaseConnection->exec_SELECTgetRows(
			'*', $sourceTable, '', '', '', $limitRecords
		);
		$count = count($toMigrate);

		if ($count <= 0) {
			throw new Exception\NoData(
				sprintf($this->lang['noData'], $sourceTable)
			);
		}

		// translate rows to insertable data according to $propertyMap
		$toInsert = $this->translatePropertiesOfRows($toMigrate, $propertyMap);

		// insert, note that we use $propertyMap to define the $fields parameter
		$this->insertTableRecords($targetTable, $propertyMap, $toInsert);

		// remove the old data
		if ($count < $limitRecords) {
			// if there are definitely no more rows to convert,
			// then truncate is quicker and cleaner than delete
			$this->databaseConnection->exec_TRUNCATEquery($sourceTable);
		} else {
			// remove every migrated uid_local/foreign combination
			$whereArray = array();
			$intersect = array(
				'uid_local' => 1,
				'uid_foreign' => 1
			);
			foreach ($toMigrate as $row) {
				$whereArray[] = $this->getWhereFromConditionArray(
					array_intersect_key($row, $intersect),
					$sourceTable
				);
			}
			$this->deleteTableRecords(
				$sourceTable,
				'(' . join(') OR (', $whereArray) . ')'
			);
		}

		return $count;
	}

	/**
	 * Creates unique records from values taken from source.
	 *
	 * Note that this only works with tables using an AUTO_INCREMENT uid,
	 * as we rely on insert_id
	 *
	 * @param string $sourceTable
	 * @param string $targetTable
	 * @param array $propertyMap Contains sourceProperty => targetProperty mappings
	 * @param array $evaluation Contains database-evaluations used to select valid unique values
	 * @param integer $limitRecords
	 * @param array &$uidMap Reference for storing targetUid => array(sourceProperty => value) mappings
	 * @throws Exception\NoData Nothing to migrate
	 * @return integer Affected record count
	 */
	public function createUniqueRecordsFromValues($sourceTable, $targetTable, array $propertyMap, array $evaluation = array(), $limitRecords = 10000, array &$uidMap = array()) {
		$uniqueBy = join(',', array_keys($propertyMap));
		// select all unique propertymap combinations
		$toInsert = $this->databaseConnection->exec_SELECTgetRows(
			$uniqueBy, $sourceTable, join(' AND ', $evaluation), $uniqueBy, $uniqueBy, $limitRecords
		);
		$count = count($toInsert);

		if ($count <= 0) {
			throw new Exception\NoData(
				sprintf($this->lang['noUniqueData'], $targetTable)
			);
		}

		// find already existing matches
		$uidMap = $this->insertUniqueRecords($targetTable, $propertyMap, $toInsert);
		return $this->databaseConnection->sql_affected_rows();
	}

	/**
	 * Mass replaces a property value with a targetValue for those records
	 * who meet the paired conditions.
	 *
	 * @param string $table
	 * @param string $property
	 * @param array $valueConditionMap targetValue => condition-array(property => value)
	 * @return integer Affected record count
	 */
	public function updatePropertyByCondition($table, $property, array $valueConditionMap) {
		$count = 0;
		foreach ($valueConditionMap as $targetValue => $conditions) {
			$values = array(
				$property => $targetValue
			);
			$count += $this->updateTableRecords($table, $values, $conditions);
		}
		return $count;
	}

	/**
	 * Mass replace values on a single table property.
	 *
	 * If you're replacing uid references with this method,
	 * enabling $strictlyUidReferences will report any issues
	 * that are of utmost importance for those. For instance,
	 * sourceUids need to be offered in ascending order so that
	 * targetUids will never be higher than sourceUids. If they
	 * are, targets will end up being overlapped by sources,
	 * throwing off the ENTIRE update-mechanism.
	 *
	 * @param string $table
	 * @param string $property
	 * @param array $valueMap sourceValue => targetValue
	 * @param boolean $strictlyUidReferences
	 * @return integer Affected record count
	 * @throws \Exception
	 */
	public function updatePropertyBySourceValue($table, $property, array $valueMap, $strictlyUidReferences = FALSE) {
		$count = 0;
		foreach ($valueMap as $sourceValue => $targetValue) {
			if ($strictlyUidReferences && (int) $targetValue > (int) $sourceValue) {
				// throwing a normal exception to stop updating entirely
				throw new \Exception(
					sprintf(
						$this->lang['target>source'],
						$table,
						$property,
						$sourceValue,
						$targetValue
					)
				);
			}

			$values = array(
				$property => $targetValue
			);
			$where = array(
				$property => $sourceValue
			);
			$count += $this->updateTableRecords($table, $values, $where);
		}
		return $count;
	}

	/**
	 * Update table records wrapper function that will format $where
	 * automatically for you.
	 *
	 * @param string $table
	 * @param array $values
	 * @param array $whereArray
	 * @return integer Affected record count
	 */
	public function updateTableRecords($table, array $values, array $whereArray = array()) {
		$where = empty($whereArray) ? '' : $this->getWhereFromConditionArray($whereArray, $table);
		$this->databaseConnection->exec_UPDATEquery($table, $where, $values);
		return $this->databaseConnection->sql_affected_rows();
	}

	/**
	 * Select $table records that meet $where condition, sorted
	 * by uid (ASC) and returns them in an array with uid as key.
	 *
	 * @param string $table
	 * @param string $where Note that an empty $where will return all
	 * @param string $select Will return all columns by default
	 * @param integer $limit Limits record count
	 * @param string $orderBy
	 * @return array
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException
	 */
	public function selectTableRecords($table, $where = '', $select = '*', $limit = 5000, $orderBy = 'uid ASC') {
		$groupBy = '';
		$rows = $this->databaseConnection->exec_SELECTgetRows(
			$select,
			$table,
			$where,
			$groupBy,
			$orderBy,
			$limit,
			'uid'
		);
		if ($rows === NULL) {
			throw new SqlErrorException(
				sprintf(
					$this->lang['sqlError'],
					$this->databaseConnection->debug_lastBuiltQuery
				)
			);
		}
		return $rows;
	}

	/**
	 * Deletes table records.
	 *
	 * @param string $table
	 * @param string $where
	 * @return integer Affected row count
	 */
	public function deleteTableRecords($table, $where = '') {
		$this->databaseConnection->exec_DELETEquery($table, $where);
		return $this->databaseConnection->sql_affected_rows();
	}

	/**
	 * Inserts multiple rows into table.
	 *
	 * @param string $table
	 * @param array $fields
	 * @param array $values Each element is an array with values
	 */
	public function insertTableRecords($table, array $fields, array $values) {
		$this->databaseConnection->exec_INSERTmultipleRows($table, $fields, $values);
		return $this->databaseConnection->sql_affected_rows();
	}



	/**
	 * Translates multiple rows' properties according to propertyMap.
	 *
	 * If any properties aren't found in the sourceRows,
	 * they will be removed from the $propertyMap reference.
	 *
	 * @param array $sourceRows
	 * @param array &$propertyMap
	 * @return array Target rows result
	 */
	protected function translatePropertiesOfRows(array $sourceRows, array &$propertyMap) {
		$targetRows = array();
		foreach ($sourceRows as $row) {
			$targetRows[] = $this->translatePropertiesOfRow($row, $propertyMap);
		}
		return $targetRows;
	}

	/**
	 * Translates single row's properties according to propertyMap.
	 *
	 * If any properties aren't found in the sourceRow,
	 * they will be removed from the $propertyMap reference.
	 *
	 * @param array $sourceRow
	 * @param array &$propertyMap
	 * @return array Target row result
	 */
	protected function translatePropertiesOfRow(array $sourceRow, array &$propertyMap) {
		$targetRow = array();
		foreach ($propertyMap as $sourceProperty => $targetProperty) {
			if (isset($sourceRow[$sourceProperty])) {
				$targetRow[$targetProperty] = $sourceRow[$sourceProperty];
			} else {
				// prevents it being iterated over for every row
				unset($propertyMap[$sourceProperty]);
			}
		}
		return $targetRow;
	}

	/**
	 * Creates a where string from a condition array
	 *
	 * @param array $conditions Contains property => value combinations
	 * @param string $table Table from which the conditions originate
	 * @return string
	 */
	protected function getWhereFromConditionArray(array $conditions, $table = NULL) {
		$where = array();
		foreach ($conditions as $property => $value) {
			$where[] = $property . '=' . $this->databaseConnection->fullQuoteStr($value, $table);
		}
		return join(' AND ', $where);
	}

	/**
	 * Insert unique records, while checking for any existing ones.
	 * Will return an UidMap that contains all relevant Uid references,
	 * both new and existing ones.
	 *
	 * @param string $table
	 * @param array $properties sourceProperties => targetProperties
	 * @param array $valueRows
	 * @throws \Exception Unexpected no-match
	 * @return array UidMap containing uid => array(property => value)
	 */
	protected function insertUniqueRecords($table, array $properties, array $valueRows) {
		$uidMap = array();
		// create where-conditions per valueRow so as to find specific matches
		$whereArray = array();
		foreach ($valueRows as $row) {
			$whereArray[] = $this->getWhereFromConditionArray(
				array_combine($properties, $row),
				$table
			);
		}
		// select without limit
		$matches = $this->selectTableRecords(
			$table,
			'(' . join(') OR (', $whereArray) . ')',
			'*',
			''
		);

		// if there are existing matches, we need to trim $values
		if (!empty($matches)) {
			$trimmedValues = array();
			foreach ($valueRows as $row) {
				$trimmedValues[md5(join('|', $row))] = $row;
			}
			// we need the target properties as key in a moment
			$flippedProperties = array_flip($properties);
			foreach ($matches as $uid => $row) {
				// only get the part of $row that matches keys with the target properties,
				// in case $matches holds more properties than $valueRows does
				$row = array_intersect_key($row, $flippedProperties);
				$index = join('|', $row);
				$indexHashed = md5($index);
				if (!isset($trimmedValues[$indexHashed])) {
					throw new \Exception(
						sprintf($this->lang['unexpectedNoMatch'], $table, $index)
					);
				}
				unset($trimmedValues[$indexHashed]);
				// register the known values with their uid already
				// row contains target properties, but uidMap needs source properties
				$uidMap[$uid] = $this->translatePropertiesOfRow($row, $flippedProperties);
			}
			$valueRows = $trimmedValues;
		}

		if (!empty($valueRows)) {
			// insert
			$this->insertTableRecords($table, $properties, $valueRows);
			// retrieve new uid's and register them with the newly stored data
			$uid = $this->databaseConnection->sql_insert_id();
			foreach ($valueRows as $row) {
				$uidMap[$uid++] = $row;
			}
		}

		return $uidMap;
	}

}
