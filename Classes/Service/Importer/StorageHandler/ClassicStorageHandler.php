<?php
namespace Innologi\Decosdata\Service\Importer\StorageHandler;
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
use Innologi\Decosdata\Exception\SqlError;
use Innologi\Decosdata\Library\FalApi\Exception\FileException;
use Innologi\Decosdata\Library\TraceLogger\TraceLoggerAwareInterface;
use Innologi\Decosdata\Service\Importer\Exception\InvalidItemBlob;
use Innologi\Decosdata\Service\Importer\Exception\InvalidItem;
/**
 * Importer Storage Handler: Classic Edition
 *
 * Handles storage of parsed import via direct queries. Although this implementation
 * has an enormous speed-benefit as opposed to other StorageHandlers, it will not
 * update TYPO3-specific reference fields, with the exception of FileReferences.
 * However, the extension doesn't really rely on them to function properly.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ClassicStorageHandler implements StorageHandlerInterface,SingletonInterface,TraceLoggerAwareInterface {
	use \Innologi\Decosdata\Library\TraceLogger\TraceLoggerAware;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var \Innologi\Decosdata\Service\Database\DatabaseHelper
	 * @inject
	 */
	protected $databaseHelper;

	/**
	 * @var \Innologi\Decosdata\Library\FalApi\FileReferenceRepository
	 * @inject
	 */
	protected $fileReferenceRepository;

	/**
	 * @var array
	 */
	protected $propertyDefaults = array();

	/**
	 * @var array
	 */
	protected $itemFields = array();

	/**
	 * @var array
	 */
	protected $itemTypeCache = array();

	/**
	 * @var array
	 */
	protected $fieldCache = array();

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->propertyDefaults = array(
			'pid' => 1,
			'crdate' => $GLOBALS['EXEC_TIME'],
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'deleted' => 0
			// leave enableFields alone, so that an import/update won't affect them
		);
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
		$this->databaseConnection->store_lastBuiltQuery = TRUE;
		// @TODO ___utilize $this->databaseConnection->sql_error() ?
	}

	/**
	 * Initialize Storage Handler
	 *
	 * This will allow the importer to set specific parameters
	 * that are of importance.
	 *
	 * @param integer $pid
	 * @return void
	 */
	public function initialize($pid) {
		if($this->logger && $this->logger->getLevel() > 1) $this->logger->logTrace();

		$this->propertyDefaults['pid'] = $pid;
		$this->fileReferenceRepository->setStoragePid($pid);
	}

	/**
	 * Push an item ready for commit.
	 *
	 * @param array $data
	 * @return integer
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\InvalidItem
	 */
	public function pushItem(array $data) {
		if($this->logger && $this->logger->getLevel() > 1) $this->logger->logTrace();

		if (!isset($data['item_key'][0])) {
			// item key is empty
			throw new InvalidItem(1448550839, array(
				'NULL',
				'no item_key'
			));
		}

		$table = 'tx_decosdata_domain_model_item';
		$insert = array_merge(
			$this->propertyDefaults,
			array(
				'item_key' => $data['item_key'],
				'item_type' => $this->getItemTypeUid($data['item_type'])
			)
		);

		$this->databaseHelper->execUpsertQuery($table, $insert, array('pid', 'item_key'));
		$uid = $this->databaseHelper->getLastUid();
		if ($uid === NULL) {
			$uid = $this->databaseHelper->getLastUidOfMatch($table, array(
				'pid' => $insert['pid'],
				'item_key' => $insert['item_key']
			));
		}

		// retrieves any previously stored itemFields of updated item or none if inserted
		$this->itemFields = $this->databaseHelper->getLastUpsertIsNewRecord()
			? array()
			: $this->getItemFields($uid, $insert['pid']);

		// push import-relation
		$table = 'tx_decosdata_item_import_mm';
		/* @var $import \Innologi\Decosdata\Domain\Model\Import */
		foreach ($data['import'] as $import) {
			$this->databaseHelper->insertMmRelationIfNotExists($table, $uid, $import->getUid());
		}

		// push parent-relation
		if ($data['parent_item'] !== NULL) {
			$table = 'tx_decosdata_item_item_mm';
			$this->databaseHelper->insertMmRelationIfNotExists($table, $uid, (int) $data['parent_item']);
		}

		return $uid;
	}

	/**
	 * Push an itemblob ready for commit.
	 *
	 * @param array $data
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\InvalidItemBlob
	 */
	public function pushItemBlob(array $data) {
		if($this->logger && $this->logger->getLevel() > 1) $this->logger->logTrace();

		if (!isset($data['item_key'][0])) {
			// item key is empty
			throw new InvalidItemBlob(1448550925, array(
				'NULL',
				'no item_key'
			));
		}

		try {
			if (! (isset($data['filepath'][0]) && is_file($data['filepath'])) ) {
				// filepath missing or not a file
				throw new FileException(1448550944, array($data['filepath'] ?? 'NULL'));
			}

			$filePath = $data['filepath'];
			unset($data['filepath']);

			$table = 'tx_decosdata_domain_model_itemblob';
			$data = array_merge($this->propertyDefaults, $data);
			$this->databaseHelper->execUpsertQuery($table, $data, array('pid', 'item_key'));
			$uid = $this->databaseHelper->getLastUid();
			if ($uid === NULL) {
				$uid = $this->databaseHelper->getLastUidOfMatch($table, array(
					'pid' => $data['pid'],
					'item_key' => $data['item_key']
				));
			}
			// @TODO ___note that we can get an exception here, after upsert. So we have to take in account that itemblobs could exist in DB without a file reference
			$this->pushFileReference($filePath, $table, $uid, 'file');
		} catch (FileException $e) {
			// if there is no correct file, there is no valid item blob
			throw new InvalidItemBlob($e->getCode(), array(
				$data['item_key'], $e->getMessage()
			));
		}
	}

	/**
	 * Push an itemfield ready for commit.
	 *
	 * @param array $data
	 * @return void
	 */
	public function pushItemField(array $data) {
		if($this->logger && $this->logger->getLevel() > 2) $this->logger->logTrace();

		$table = 'tx_decosdata_domain_model_itemfield';
		$data = array_merge($this->propertyDefaults, $data);
		$data['field'] = $this->getFieldUid($data['field']);

		$whereData = array(
			'field' => $data['field'],
			'item' => $data['item'],
			'pid' => $data['pid']
		);

		if ($data['field_value'] === NULL) {
			// if fieldvalue is NULL while the field previously existed with a value for this item, remove it
			if (isset($this->itemFields[$data['field']])) {
				$data['deleted'] = 1;
				$this->databaseConnection->exec_UPDATEquery(
					$table,
					$this->databaseHelper->getWhereFromConditionArray($whereData, $table),
					$data
				);
			}
			// otherwise, just don't register it
		} else {
			// if we have a field value, always upsert
			$this->databaseHelper->execUpsertQuery($table, $data, array_keys($whereData));
		}
	}

	/**
	 * Push a file reference ready for commit.
	 *
	 * @param string $filePath
	 * @param string $foreignTable
	 * @param integer $foreignUid
	 * @param string $foreignField
	 * @return void
	 */
	protected function pushFileReference($filePath, $foreignTable, $foreignUid, $foreignField) {
		if($this->logger && $this->logger->getLevel() > 2) $this->logger->logTrace();

		$this->fileReferenceRepository->upsertRecordByFilePath($filePath, $foreignTable, $foreignUid, $foreignField);
	}

	/**
	 * Commits all pushed data.
	 *
	 * @return void
	 */
	public function commit() {
		// doesn't do anything in this implementation
	}

	/**
	 * Returns itemType uid by itemType string
	 *
	 * @param string $type
	 * @return integer
	 */
	protected function getItemTypeUid($type) {
		$cacheKey = $type . ';;;' . $this->propertyDefaults['pid'];
		if (!isset($this->itemTypeCache[$cacheKey])) {
			$this->itemTypeCache[$cacheKey] = $this->produceValueObjectUid(
				'tx_decosdata_domain_model_itemtype',
				array('item_type' => $type)
			);
		}
		return $this->itemTypeCache[$cacheKey];
	}

	/**
	 * Returns field uid by fieldname string
	 *
	 * @param string $field
	 * @return integer
	 */
	protected function getFieldUid($field) {
		$cacheKey = $field . ';;;' . $this->propertyDefaults['pid'];
		if (!isset($this->fieldCache[$cacheKey])) {
			$this->fieldCache[$cacheKey] = $this->produceValueObjectUid(
				'tx_decosdata_domain_model_field',
				array('field_name' => $field)
			);
		}
		return $this->fieldCache[$cacheKey];
	}

	/**
	 * Produces an uid of a valueobject record, either by retrieving
	 * an existing record or inserting a new one.
	 *
	 * @param string $table
	 * @param array $data Contains value-defining property => value
	 * @return integer
	 */
	protected function produceValueObjectUid($table, array $data) {
		$data['pid'] = $this->propertyDefaults['pid'];
		$data['deleted'] = 0;

		// first see if it exists at all (and not deleted)
		$uid = $this->databaseHelper->getLastUidOfMatch($table, $data);
		if ($uid === NULL) {
			$this->databaseConnection->exec_INSERTquery($table, array_merge(
				$this->propertyDefaults,
				$data
			));
			$uid = $this->databaseConnection->sql_insert_id();
		}
		return $uid;
	}

	/**
	 * Returns all known itemfields belonging to the given item and page combination,
	 * _if_ not marked for deletion, in the format:
	 * fieldUid => array(`field` => fieldUid,`field_value` => value)
	 *
	 * @param integer $itemUid
	 * @param integer $pageUid
	 * @return array
	 * @throws \Innologi\Decosdata\Exception\SqlError
	 */
	protected function getItemFields($itemUid, $pageUid) {
		$table = 'tx_decosdata_domain_model_itemfield';
		$rows = $this->databaseConnection->exec_SELECTgetRows(
			'field, field_value',
			$table,
			$this->databaseHelper->getWhereFromConditionArray(array(
				'item' => $itemUid,
				'pid' => $pageUid,
				// if not 0, the field is no longer relevant
				'deleted' => 0
			), $table),
			'',
			'',
			'',
			'field'
		);

		if ($rows === NULL) {
			throw new SqlError(1448551035, array(
				$this->databaseConnection->debug_lastBuiltQuery
			));
		}

		return $rows;
	}

}
