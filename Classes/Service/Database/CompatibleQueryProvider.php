<?php
namespace Innologi\Decospublisher7\Service\Database;
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
/**
 * Query Provider: Compatible edition
 *
 * Provides special queries for which database engines have optimized
 * functions, but which we won't use here to remain compatible. Generally,
 * you would use this class as a fallback.
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CompatibleQueryProvider implements QueryProviderInterface,SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var \Innologi\Decospublisher7\Service\Database\DatabaseHelper
	 * @inject
	 */
	protected $databaseHelper;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		// @TODO ___utilize $this->databaseConnection->sql_error() ?
	}

	/**
	 * An upsert, or InsertOrUpdateOnDuplicate: will attempt to insert a record,
	 * or update en existing one if its data matches an existing record.
	 *
	 * @param string $table
	 * @param array $data Contains property => value elements
	 * @param array $uniqueProperties (optional)
	 * @return string The upsert query
	 */
	public function upsertQuery($table, array $data, array $uniqueProperties = array()) {
		$query = NULL;

		$matchProperties = empty($uniqueProperties)
			? $data
			: array_intersect_key(
				$data,
				array_flip($uniqueProperties)
			);

		$uid = $this->databaseHelper->getLastUidOfMatch($table, $matchProperties);
		if ($uid === NULL) {
			$query = $this->databaseConnection->INSERTquery($table, $data);
		} else {
			if ($updateParameters['crdate']) {
				// we never want to update the crdate property value
				unset($data['crdate']);
			}
			$query = $this->databaseConnection->UPDATEquery(
				$table,
				'uid = ' . $this->databaseConnection->fullQuoteStr($uid, $table),
				$data
			);
		}

		return $query;
	}

}
