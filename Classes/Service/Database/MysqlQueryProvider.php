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

/**
 * Query Provider: MySQL edition
 *
 * Provides special queries that differ in implementation per database
 * engine. In this case, the queries are specifially designed to utilize
 * optimized MySQL-features.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MysqlQueryProvider extends CompatibleQueryProvider {

	/**
	 * An upsert, or InsertOrUpdateOnDuplicate: will attempt to insert a record,
	 * or update an existing one if its data matches an existing record.
	 *
	 * Optionally, you can provide uniqueProperties which will contain the property
	 * keynames from $data which define an existing record. This is useful when you
	 * cannot rely on unique key being set on properties other than uid. In TYPO3,
	 * this is almost always the case when you don't know the uid, where the table
	 * has valid TCA and also supports the `deleted` flag, because it would provide
	 * serious duplicate key issues. Only provide the argument in those cases, because
	 * it will force the method to fallback to the CompatibleQueryProvider implementation.
	 *
	 * @param string $table
	 * @param array $data Contains property => value elements
	 * @param array $uniqueProperties (optional)
	 * @return string The upsert query
	 */
	public function upsertQuery($table, array $data, array $uniqueProperties = []) {
		if (!empty($uniqueProperties)) {
			// fallback to compatibleQueryProvider
			return parent::upsertQuery($table, $data, $uniqueProperties);
		}

		// escape values
		$data = $this->databaseConnection->fullQuoteArray($data, $table);

		// provide the UPDATE parameters in another format
		$updateParameters = [];
		foreach ($data as $property => $value) {
			$updateParameters[$property] = $property . '=' . $value;
		}
		if ($updateParameters['crdate']) {
			// we never want to update the crdate property value
			unset($updateParameters['crdate']);
		}

		$query = sprintf(
			'INSERT INTO %1$s (%2$s) VALUES (%3$s) ON DUPLICATE KEY UPDATE %4$s',
			$table,
			// fields
			implode(',', array_keys($data)),
			// values
			implode(',', $data),
			implode(',', $updateParameters)
		);

		return $query;
	}

}
