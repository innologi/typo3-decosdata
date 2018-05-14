<?php
namespace Innologi\Decosdata\Service\Database;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
use TYPO3\CMS\Core\Database\DatabaseConnection;
use Innologi\Decosdata\Exception\SqlError;
/**
 * Database Helper
 *
 * Provides much used methods for constructing or executing database queries
 * the non-FLOW way.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DatabaseHelper implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var \Innologi\Decosdata\Service\Database\QueryProviderInterface
	 * @inject
	 */
	protected $queryProvider;

	/**
	 * @var integer
	 */
	protected $lastUid = NULL;

	/**
	 * @var boolean
	 */
	protected $lastUpsertIsNewRecord = FALSE;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		$this->databaseConnection->store_lastBuiltQuery = TRUE;
		// @TODO ___utilize $this->databaseConnection->sql_error() ?
	}

	/**
	 * Execute an upsert, or InsertOrUpdateOnDuplicate: will attempt to insert a record,
	 * or update en existing one if its data matches an existing record.
	 *
	 * @param string $table
	 * @param array $data Contains property => value elements
	 * @param array $uniqueProperties (optional)
	 * @return object|boolean Query Result
	 */
	public function execUpsertQuery($table, array $data, array $uniqueProperties = []) {
		$this->lastUid = NULL;
		$this->lastUpsertIsNewRecord = FALSE;
		$res = $this->databaseConnection->sql_query(
			$this->queryProvider->upsertQuery($table, $data, $uniqueProperties)
		);

		$uid = $this->databaseConnection->sql_insert_id();
		if ($uid > 0) {
			$this->lastUid = $uid;
			$this->lastUpsertIsNewRecord = TRUE;
		}

		return $res;
	}

	/**
	 * Inserts an MM table record, but only if it doesn't exist.
	 *
	 * @param string $table
	 * @param integer $localUid
	 * @param integer $foreignUid
	 * @return void
	 */
	public function insertMmRelationIfNotExists($table, $localUid, $foreignUid) {
		if (!$this->doesMmMatchExist($table, $localUid, $foreignUid)) {
			$this->databaseConnection->exec_INSERTquery($table, [
				'uid_local' => $localUid,
				'uid_foreign' => $foreignUid
			]);
		}
	}


	/**
	 * Creates a where string from a condition array
	 *
	 * @param array $conditions Contains property => value combinations
	 * @param string $table Table from which the conditions originate
	 * @return string
	 */
	public function getWhereFromConditionArray(array $conditions, $table = NULL) {
		$where = [];
		foreach ($conditions as $property => $value) {
			$where[] = $property . '=' . $this->databaseConnection->fullQuoteStr($value, $table);
		}
		return join(' ' . DatabaseConnection::AND_Constraint . ' ', $where);
	}

	/**
	 * Returns uid of newest record that matches $data parameters.
	 *
	 * @param string $table
	 * @param array $data
	 * @return integer|NULL
	 * @throws \Innologi\Decosdata\Exception\SqlError
	 */
	public function getLastUidOfMatch($table, array $data) {
		$row = $this->databaseConnection->exec_SELECTgetSingleRow(
			'uid',
			$table,
			$this->getWhereFromConditionArray($data),
			'',
			'uid DESC'
		);

		if ($row === NULL) {
			throw new SqlError(1448550406, [
				$this->databaseConnection->debug_lastBuiltQuery
			]);
		}
		$this->lastUid = $row === FALSE
			? NULL
			: (int) $row['uid'];

		return $this->lastUid;
	}

	/**
	 * Returns whether an MM relation match exists.
	 *
	 * @param string $table
	 * @param integer $localUid
	 * @param integer $foreignUid
	 * @return boolean
	 */
	public function doesMmMatchExist($table, $localUid, $foreignUid) {
		$row = $this->databaseConnection->exec_SELECTgetSingleRow(
			'*',
			$table,
			$this->getWhereFromConditionArray([
				'uid_local' => $localUid,
				'uid_foreign' => $foreignUid
			]),
			'',
			'uid_local DESC,uid_foreign DESC'
		);

		return is_array($row);
	}

	/**
	 * Returns last uid stored by any exec*() or getLastUid*() methods of this class.
	 *
	 * @return integer|NULL
	 */
	public function getLastUid() {
		return $this->lastUid;
	}

	/**
	 * Returns whether the last executed upsert query resulted in a new record.
	 *
	 * @return boolean
	 */
	public function getLastUpsertIsNewRecord() {
		return $this->lastUpsertIsNewRecord;
	}

}
