<?php
namespace Innologi\Decosdata\Library\FalApi;
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * File Reference repository
 *
 * Note that this repository does not follow FLOW conventions and
 * is highly dependent of TYPO3 CMS' DatabaseConnection and DataHandler
 * classes.
 *
 * Should only be used in use-cases where FLOW/Extbase persistence
 * is disabled or not available. Otherwise you should use the
 * FileReferenceFactory and simply persist its parentObject.
 *
 * @package InnologiLibs
 * @subpackage FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FileReferenceRepository implements SingletonInterface {

	/**
	 * @var string
	 */
	protected $referenceTable = 'sys_file_reference';

	/**
	 * @var string
	 */
	protected $localTable = 'sys_file';

	/**
	 * @var integer
	 */
	protected $storagePid = 0;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 * @inject
	 */
	protected $dataHandler;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 * @inject
	 */
	protected $resourceFactory;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $beUser;


	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		// this is enough to keep our DataHandler-method-calls from failing outside of BE
		$this->beUser = isset($GLOBALS['BE_USER'])
			? $GLOBALS['BE_USER']
			: GeneralUtility::makeInstance(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
		// @TODO ___temp, remove once instructions are made?
		$this->beUser->user['admin'] = 1;
	}

	/**
	 * Add a File Reference record via DataHandler
	 *
	 * Optionally it can be used to update an existing record by providing a $referenceUid
	 *
	 * @param integer $fileUid
	 * @param string $foreignTable
	 * @param integer $foreignUid
	 * @param string $foreignField
	 * @param string $referenceUid (optional)
	 * @return void
	 * @throws Exception\FileReferenceException
	 */
	public function addRecord($fileUid, $foreignTable, $foreignUid, $foreignField, $referenceUid = NULL) {
		if ($referenceUid === NULL) {
			$referenceUid = 'NEW' . $fileUid;
		}

		$data = array(
			$this->referenceTable => array(
				$referenceUid => array(
					'uid_local' => $fileUid,
					'table_local' => $this->localTable,
					'uid_foreign' => $foreignUid,
					'tablenames' => $foreignTable,
					'fieldname' => $foreignField,
					'pid' => $this->storagePid
				)
			),
			// immediately update the reference in foreign table
			$foreignTable => array(
				$foreignUid => array(
					$foreignField => $referenceUid
				)
			)
		);

		$this->dataHandler->start($data, array(), $this->beUser);
		$this->dataHandler->process_datamap();
		if ($this->dataHandler->errorLog) {
			throw new Exception\FileReferenceException(1448550062, array(
				DebugUtility::viewArray($this->dataHandler->errorLog)
			));
		}
	}

	/**
	 * Add a File Reference record via DataHandler
	 *
	 * Differs from addRecord() by the first parameter being a filePath
	 * which will automatically be resolved into a valid sys_file uid.
	 *
	 * Optionally it can be used to update an existing record by providing a $referenceUid
	 *
	 * @param string $filePath
	 * @param string $foreignTable
	 * @param integer $foreignUid
	 * @param string $foreignField
	 * @param string $referenceUid (optional)
	 * @return void
	 * @throws Exception\FileException
	 * @see addRecord()
	 */
	public function addRecordByFilePath($filePath, $foreignTable, $foreignUid, $foreignField, $referenceUid = NULL) {
		$fileObject = $this->resourceFactory->retrieveFileOrFolderObject($filePath);
		if ( !($fileObject instanceof \TYPO3\CMS\Core\Resource\File) ) {
			throw new Exception\FileException(1448550118, array($filePath));
		}
		$this->addRecord($fileObject->getUid(), $foreignTable, $foreignUid, $foreignField, $referenceUid);
	}

	/**
	 * Adds or updates a File Reference record via DataHandler,
	 * by automatically determining whether the reference already
	 * exists in the reference table.
	 *
	 * Note that if the reference points to a different fileUid,
	 * the original reference will be lost. We do this because in
	 * all of our use-cases, there should never be more than one
	 * ONE file reference per foreign record.
	 *
	 * @param integer $fileUid
	 * @param string $foreignTable
	 * @param integer $foreignUid
	 * @param string $foreignField
	 * @return void
	 * @see addRecord()
	 */
	public function upsertRecord($fileUid, $foreignTable, $foreignUid, $foreignField) {
		$row = $this->findOneByData(array(
			'table_local' => $this->localTable,
			'uid_foreign' => $foreignUid,
			'tablenames' => $foreignTable,
			'fieldname' => $foreignField
		));

		if ($row !== FALSE) {
			// one found: only update it if $fileUid has changed
			if ((int) $row['uid_local'] !== $fileUid) {
				$this->addRecord($fileUid, $foreignTable, $foreignUid, $foreignField, (int) $row['uid']);
			}
		} else {
			// none found: just add is as new
			$this->addRecord($fileUid, $foreignTable, $foreignUid, $foreignField);
		}
	}

	/**
	 * Adds or updates a File Reference record via DataHandler,
	 * by automatically determining whether the reference already
	 * exists in the reference table.
	 *
	 * Differs from upsertRecord() by the first parameter being a filePath
	 * which will automatically be resolved into a valid sys_file uid.
	 *
	 * @param integer $fileUid
	 * @param string $foreignTable
	 * @param integer $foreignUid
	 * @param string $foreignField
	 * @return void
	 * @throws Exception\FileException
	 * @see upsertRecord()
	 */
	public function upsertRecordByFilePath($filePath, $foreignTable, $foreignUid, $foreignField) {
		$fileObject = $this->resourceFactory->retrieveFileOrFolderObject($filePath);
		if ( !($fileObject instanceof \TYPO3\CMS\Core\Resource\File) ) {
			throw new Exception\FileException(1448550330, array($filePath));
		}
		$this->upsertRecord($fileObject->getUid(), $foreignTable, $foreignUid, $foreignField);
	}

	/**
	 * Returns a single reference record that matches $data
	 * conditions.
	 *
	 * @param array $data Contains property => value conditions
	 * @return array|boolean
	 * @throws Exception\SqlError
	 */
	public function findOneByData(array $data) {
		/* @var $databaseConnection \TYPO3\CMS\Core\Database\DatabaseConnection */
		$databaseConnection = $GLOBALS['TYPO3_DB'];
		$databaseConnection->store_lastBuiltQuery = TRUE;

		$data['pid'] = $this->storagePid;
		$data['deleted'] = 0;

		$where = array();
		foreach ($data as $property => $value) {
			$where[] = $property . '=' . $databaseConnection->fullQuoteStr($value, $this->referenceTable);
		}
		$where = empty($where) ? '' : join(' ' . DatabaseConnection::AND_Constraint . ' ', $where);

		$row = $databaseConnection->exec_SELECTgetSingleRow(
			'*',
			$this->referenceTable,
			$where,
			'',
			'uid DESC'
		);

		if ($row === NULL) {
			throw new Exception\SqlError(1448550356, array(
				$databaseConnection->debug_lastBuiltQuery
			));
		}
		return $row;
	}

	/**
	 * Sets storagePid for all database interactions of this class.
	 *
	 * @param integer $pid
	 * @return void
	 */
	public function setStoragePid($pid) {
		$this->storagePid = $pid;
	}

}