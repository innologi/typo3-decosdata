<?php
namespace Innologi\Decospublisher7\Mvc\Domain;
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
use TYPO3\CMS\Extbase\Persistence\Repository;
/**
 * General RepositoryAbstract class
 *
 * Extends original extbase repository and adds a few extras
 * this extension uses on several occassions.
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class RepositoryAbstract extends Repository {

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * Returns DatabaseConnection
	 *
	 * Using a method for this so we don't need to overrule the constructor.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Insert record data in repository table, and return the resulting uid.
	 *
	 * @param array $data
	 * @return integer
	 */
	protected function insertRecord(array $data) {
		$this->getDatabaseConnection()->exec_INSERTquery($this->table, $data);
		return $this->getDatabaseConnection()->sql_insert_id();
	}

	/**
	 * Overrides storage pid for all queries.
	 *
	 * @param integer $storagePid
	 * return void
	 */
	public function setStoragePid($storagePid) {
		/* @var $querySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
		$querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$querySettings->setStoragePageIds(array($storagePid));
		$this->setDefaultQuerySettings($querySettings);
	}
}