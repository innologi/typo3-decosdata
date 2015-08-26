<?php
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use Innologi\Decosdata\Library\ExtUpdate\ExtUpdateAbstract;
use Innologi\Decosdata\Library\ExtUpdate\Service\Exception\NoData;
use Innologi\Decosdata\Library\ExtUpdate\Service\Exception\FileException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
/**
 * Ext Update
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ext_update extends ExtUpdateAbstract {

	/**
	 * Extension key
	 *
	 * @var string
	 */
	protected $extensionKey = 'decosdata';

	/**
	 * If the extension updater is to take data from a different extension,
	 * its extension key may be set here.
	 *
	 * @var string
	 */
	protected $sourceExtensionKey = 'decospublisher';

	/**
	 * If the extension updater is to take data from a different source,
	 * its minimum version may be set here.
	 *
	 * @var string
	 */
	protected $sourceExtensionVersion = '6.1.0';
	// @TODO ___update to 6.2.0 once that version is pushed to TER

	/**
	 * Language labels => messages
	 *
	 * @var array
	 */
	protected $lang = array(
		// @TODO ___make an entry in the manual explaining how to protect files against unauthorized access
		'dirMismatch' => 'The document path \'<code>%1$s</code>\' is different from the XML Import path \'<code>%2$s</code>\'. This is no longer supported. If this was applied for security-reasons, read the corresponding entry in the manual for alternatives. %3$s',
		'dirMoveAutomatic' => 'Please move or symlink the file-directory \'<code>%1$s</code>\' so that it is available at \'<code>%2$s</code>\'. The updater will automatically detect this at the next run.',
		'dirMoveManual' => 'Unfortunately, the updater cannot detect the corresponding file-directory in the document path. (assumed to be \'<code>%1$s</code>\') Please move or symlink it so that it becomes available within the xml import path, then change the \'<code>%2$s</code>\' record manually to reflect this. Note that you cannot do this via TCA unless you revert to the previous decospublisher version first.',
		'falMigrateSuccess' => 'Migrated %2$d \'<code>%1$s</code>\' records to FAL.',
		'falMigrateFail' => 'Cannot migrate file to FAL.',
		'falMigrateFailCorrect' => 'You should correct this issue. You can move the file to a location within e.g. \'<code>%1$s</code>\' and will have to correct the path within the \'<code>%2$s</code>\' record, which you cannot do from TCA unless you revert to the previous decospublisher version first',
		'falMigrateFailDelete' => 'The file registration will hence be removed.',
		'falMigrateFailTitle' => '%1$s FAL migration: <code>%2$s</code>',
		'migrateManual' => 'Due to necessary core changes in the extension, an automated migration of %1$s is not possible. %2$s: <code>%3$s</code>',
		'migrateManualTitle' => 'Can not automatically migrate %1$s',
		'migrateSuccess' => 'Migrated %2$d \'<code>%1$s</code>\' records.',
		'migrateWait' => 'Prerequisites for \'<code>%1$s</code>\' record migration not yet met.',
		'nNothing' => 'No %1$s to %2$s.',
		'pluginCreate' => 'It is recommended to create new plugins and delete the old ones once succesfully re-created. Only then will they disappear from the following list',
		'profileImport' => 'You will need to re-import the profiles if you wish to use them. The following profiles were found to be not yet re-imported',
		'referenceUpdate' => 'Updated %2$d references in \'<code>%1$s</code>\'.',
		'updaterFinish' => 'The updater has finished all of its tasks, you don\'t need to run it again until the next extension-update.',
		'updaterFinishTitle' => 'Update complete',
		'updaterRepeat' => 'Please run the updater again to continue updating and/or follow any remaining instructions, until this message disappears.',
		'updaterRepeatTitle' => 'Run updater again',
	);

	/**
	 * Registers which migrations are finished, so that migrations
	 * with prerequisites can determine if they can start.
	 *
	 * @var array
	 */
	protected $finishState = array(
		'blob' => FALSE,
		'fal_blob' => FALSE,
		'fal_itemxml' => FALSE,
		'field' => FALSE,
		'item' => FALSE,
		'item_item_mm' => FALSE,
		'item_xml_mm' => FALSE,
		'itemfield' => FALSE,
		'itemtype' => FALSE,
		'itemxml' => FALSE,
		'itemxml_filedirpath' => FALSE,
		'plugins' => FALSE,
		'profiles' => FALSE,
	);

	/**
	 * Provides the methods to be executed during update.
	 *
	 * @return void
	 */
	public function processUpdates() {
		// @TODO ___decided to make this extension a new one, which means a change in i.e. reference updates
		// the order is important for some of these!
		// then do xml/import, as it's better to find out sooner than later if something goes wrong here
		$this->migrateXmlToFal();
		$this->migrateXmlFileDir();
		$this->migrateItemXmlMmTable();
		$this->migrateXmlTable();
		// then imigrate anything filepath/blob related
		$this->migrateBlobToFal();
		$this->migrateBlobs();
		// then import items and metadata, can't really go wrong here
		$this->migrateItemTypes();
		$this->migrateFields();
		$this->migrateItemFieldTable();
		$this->migrateItemItemMmTable();
		$this->migrateItemTable();
		// do some checks that give warnings/instructions for some things
		$this->checkProfiles();
		$this->checkPlugins();

		// if even one finishState is FALSE, we'll add the instruction to run the updater again
		if (in_array(FALSE, $this->finishState, TRUE)) {
			$this->addFlashMessage($this->lang['updaterRepeat'], $this->lang['updaterRepeatTitle'], FlashMessage::WARNING);
		} else {
			$this->addFlashMessage($this->lang['updaterFinish'], $this->lang['updaterFinishTitle'], FlashMessage::OK);
		}

		// @TODO ___Task Migration ('auto' property)
	}



	/**
	 * Migrates itemtype values to item type table records.
	 *
	 * @return void
	 */
	protected function migrateItemTypes() {
		// stop if prior migration haven't finished
		if (!$this->finishState['blob']) {
			return;
		}

		$uidMap = array();
		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_item';
		$targetTable = 'tx_' . $this->extensionKey . '_domain_model_itemtype';
		$propertyMap = array(
			'pid' => 'pid',
			'itemtype' => 'item_type'
		);
		// @LOW ___mysql specific!
		$evaluation = array(
			'itemtype NOT RLIKE \'^[0-9]+$\''
		);

		// atempt migration
		try {
			$countRecords = $this->databaseService->createUniqueRecordsFromValues($sourceTable, $targetTable, $propertyMap, $evaluation, 20, $uidMap);
			$countReferences = $this->databaseService->updatePropertyByCondition($sourceTable, 'itemtype', $uidMap);
			$message = array(
				sprintf($this->lang['migrateSuccess'], 'itemtype', $countRecords),
				sprintf($this->lang['referenceUpdate'], $sourceTable, $countReferences)
			);
			$this->addFlashMessage(join(' ', $message), '', FlashMessage::OK);
		} catch (NoData $e) {
			// no data to migrate
			$this->addFlashMessage($e->getMessage(), '', FlashMessage::INFO);
			$this->finishState['itemtype'] = TRUE;
		}
	}

	/**
	 * Migrates field names to field table records.
	 *
	 * @return void
	 */
	protected function migrateFields() {
		// stop if prior migration haven't finished
		if (!$this->finishState['blob']) {
			return;
		}

		// @TODO ___once we include itemfieldhistory, we need to add migration of the original field table here as well
		// @TODO ___once we include importrules, we need to add migration of the original field table here as well
		$uidMap = array();
		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_itemfield';
		$targetTable = 'tx_' . $this->extensionKey . '_domain_model_field';
		$referenceTable = array(
			$sourceTable => 'fieldname',
		);
		$propertyMap = array(
			'pid' => 'pid',
			'fieldname' => 'field_name'
		);
		// @LOW ___mysql specific!
		$evaluation = array(
			'fieldname NOT RLIKE \'^[0-9]+$\''
		);

		// attempt migration
		try {
			$countRecords = $this->databaseService->createUniqueRecordsFromValues($sourceTable, $targetTable, $propertyMap, $evaluation, 20, $uidMap);
			$message = array(
				sprintf($this->lang['migrateSuccess'], 'fieldname', $countRecords)
			);
			foreach ($referenceTable as $referenceTable => $property) {
				$countReferences = $this->databaseService->updatePropertyByCondition($referenceTable, $property, $uidMap);
				$message[] = sprintf($this->lang['referenceUpdate'], $referenceTable, $countReferences);
			}
			$this->addFlashMessage(join(' ', $message), '', FlashMessage::OK);
		} catch (NoData $e) {
			// no data to migrate
			$this->addFlashMessage($e->getMessage(), '', FlashMessage::INFO);
			$this->finishState['field'] = TRUE;
		}
	}

	/**
	 * Migrate BLOB items to itemblob table.
	 *
	 * @return void
	 */
	protected function migrateBlobs() {
		// stop if prior migrations haven't finished
		if ( !($this->finishState['fal_blob']) ) {
			return;
		}

		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_item';
		$sourceDataTable = 'tx_' . $this->sourceExtensionKey . '_itemfield';
		$sourceMmTable = 'tx_' . $this->sourceExtensionKey . '_item_mm';
		$targetTable = 'tx_' . $this->extensionKey . '_domain_model_itemblob';
		$propertyMap = array(
			'pid' => 'pid',
			'item_key' => 'item_key',
			'sequence' => 'sequence',
			'file' => 'file',
			'item' => 'item',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'deleted' => 'deleted',
			'hidden' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		);

		// query to get all BLOB item records in correct order with all relevant properties attached
		$select = 'it.pid AS pid,it.itemkey AS item_key,itf1.fieldvalue AS sequence,
			itf2.fieldvalue AS file,itr.uid_foreign AS item,it.tstamp AS tstamp,
			it.crdate AS crdate,it.cruser_id AS cruser_id,it.deleted AS deleted,
			it.hidden AS hidden,it.starttime AS starttime,it.endtime AS endtime,
			it.uid AS uid,UNIX_TIMESTAMP(itf3.fieldvalue) AS docdate';
		$from = sprintf(
			'%1$s it
			LEFT JOIN %2$s itf1 ON (it.uid = itf1.item_id AND itf1.fieldname=\'SEQUENCE\')
			LEFT JOIN %2$s itf2 ON (it.uid = itf2.item_id AND itf2.fieldname=\'FILEPATH\')
			LEFT JOIN %2$s itf3 ON (it.uid = itf3.item_id AND itf3.fieldname=\'DOCUMENT_DATE\')
			LEFT JOIN %3$s itr ON (it.uid = itr.uid_local)',
			$sourceTable,
			$sourceDataTable,
			$sourceMmTable
		);
		$where = 'it.itemtype = \'BLOB\'';
		$orderBy = 'item ASC,sequence ASC,docdate DESC';
		$toMigrate = $this->databaseService->selectTableRecords($from, $where, $select, 10000, $orderBy);

		// no results means we're done migrating
		if (empty($toMigrate)) {
			$this->addFlashMessage(
				sprintf($this->lang['nNothing'], 'BLOB items', 'to migrate'),
				'',
				FlashMessage::INFO
			);
			$this->finishState['blob'] = TRUE;
			return;
		}

		// first check for:
		// - file id's not set to 0, we skip these from insert but not from delete
		// - fields that aren't in propertyMap
		// - missing sequences and provide them if necessary
		$parentItem = 0;
		$sequence = 0;
		$toInsert = array();
		foreach ($toMigrate as $uid => $row) {
			if ((int) $row['file'] > 0) {
				// it's a bit hacky, but meh, it's just a a few lines in an update script
				unset($row['docdate']);
				unset($row['uid']);
				if (isset($row['sequence'])) {
					$sequence = $row['sequence'];
					$parentItem = (int) $row['item'];
				} else {
					if ($parentItem !== (int) $row['item']) {
						// it only gets here if the entire parent-item has no blobs with a sequence set
						$sequence = 1;
						$parentItem = (int) $row['item'];
					}
					// this way, we always start with 1 or do +1 for a single parent-item
					$row['sequence'] = $sequence++;
				}

				$toInsert[] = $row;
			}
		}

		if (!empty($toInsert)) {
			// insert!
			$this->databaseService->insertTableRecords(
				$targetTable, $propertyMap, $toInsert
			);
		}

		// delete everything related to these items
		$uidWhere = ' IN (\'' . join('\',\'', array_keys($toMigrate)) . '\')';
		$deleteTables = array(
			$sourceTable => 'uid',
			$sourceDataTable => 'item_id',
			$sourceMmTable => 'uid_local'
		);
		foreach ($deleteTables as $table => $property) {
			$this->databaseService->deleteTableRecords($table, $property . $uidWhere);
		}

		$this->addFlashMessage(
			sprintf($this->lang['migrateSuccess'], $targetTable, count($toInsert)),
			'',
			FlashMessage::OK
		);
	}

	/**
	 * Migrates old item-mm table to new item-mm table
	 *
	 * @return void
	 */
	protected function migrateItemItemMmTable() {
		// stop if prior migration haven't finished --ARTIFICIAL
		if (!$this->finishState['itemfield']) {
			return;
		}

		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_item_mm';
		$targetTable = 'tx_' . $this->extensionKey . '_item_item_mm';
		$propertyMap = array(
			'uid_local' => 'uid_local',
			'uid_foreign' => 'uid_foreign',
			'sorting' => 'sorting',
			'sorting_foreign' => 'sorting_foreign'
		);

		// attempt migration
		try {
			$countRecords = $this->databaseService->migrateMmTable($sourceTable, $targetTable, $propertyMap, 10000);
			$this->addFlashMessage(
				sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
				'',
				FlashMessage::OK
			);
		} catch (NoData $e) {
			// no data to migrate
			$this->addFlashMessage($e->getMessage(), '', FlashMessage::INFO);
			$this->finishState['item_item_mm'] = TRUE;
		}
	}

	/**
	 * Migrates old item-xml-mm table to new item-xml-mm table
	 *
	 * @return void
	 */
	protected function migrateItemXmlMmTable() {
		// stop if prior migrations haven't finished --ARTIFICIAL
		if ( !($this->finishState['fal_itemxml'] && $this->finishState['itemxml_filedirpath']) ) {
			return;
		}

		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_item_itemxml_mm';
		$targetTable = 'tx_' . $this->extensionKey . '_item_import_mm';
		// @FIX ___what about the 'current' property? this is important for migrating itemblob filepaths!
		$propertyMap = array(
			'uid_local' => 'uid_local',
			'uid_foreign' => 'uid_foreign',
			'sorting' => 'sorting'
		);

		// attempt migration
		try {
			$countRecords = $this->databaseService->migrateMmTable($sourceTable, $targetTable, $propertyMap, 10000);
			$this->addFlashMessage(
				sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
				'',
				FlashMessage::OK
			);
		} catch (NoData $e) {
			// no data to migrate
			$this->addFlashMessage($e->getMessage(), '', FlashMessage::INFO);
			$this->finishState['item_xml_mm'] = TRUE;
		}
	}

	/**
	 * Migrate file-references from BLOB to FAL.
	 *
	 * @return void
	 */
	protected function migrateBlobToFal() {
		// stop if prior migrations haven't finished --ARTIFICIAL
		if ( !($this->finishState['itemxml']) ) {
			return;
		}

		$table = 'tx_' . $this->sourceExtensionKey . '_itemfield';
		// filepath used to be a concatted string
		$select = 'itf.uid AS uid,CONCAT(itx.filedirpath,itf.fieldvalue) AS filepath';
		$from = $table . ' itf
			LEFT JOIN (tx_decospublisher_item_itemxml_mm itxr,tx_decospublisher_itemxml itx)
				ON (itf.item_id=itxr.uid_local
					AND itxr.uid_foreign=itx.uid AND itxr.current=1)';
		$where = 'itf.fieldname = \'FILEPATH\' AND itf.fieldvalue NOT RLIKE \'^[0-9]+$\'';

		$toMigrate = $this->databaseService->selectTableRecords($from, $where, $select);
		// no results means we're done migrating
		if (empty($toMigrate)) {
			$this->addFlashMessage(
				sprintf($this->lang['nNothing'], 'ItemField files', 'migrate to FAL'),
				'',
				FlashMessage::INFO
			);
			$this->finishState['fal_blob'] = TRUE;
			return;
		}

		$count = 0;
		$updateValues = array();
		foreach ($toMigrate as $uid => $row) {
			// migrate itemfield file to FAL and set the reference
			$errorMessage = NULL;
			try {
				$file = $this->fileService->retrieveFileObjectByPath($row['filepath']);
				$updateValues['fieldvalue'] = $file->getUid();
				$count++;
			} catch (ResourceDoesNotExistException $e) {
				$errorMessage = $e->getMessage();
			} catch (FileException $e) {
				$errorMessage = $e->getMessage();
			}

			if ($errorMessage !== NULL) {
				$this->addFlashMessage(
					$errorMessage . ' ' . $this->lang['falMigrateFail'] . ' ' . $this->lang['falMigrateFailDelete'],
					sprintf($this->lang['falMigrateFailTitle'], 'Itemfield', 'id ' . $uid),
					FlashMessage::ERROR
				);
				$updateValues['fieldvalue'] = 0;
			}

			$this->databaseService->updateTableRecords(
				$table,
				$updateValues,
				array(
					'uid' => $uid
				)
			);
		}
		if ($count > 0) {
			$this->addFlashMessage(
				sprintf($this->lang['falMigrateSuccess'], $table, $count),
				'',
				FlashMessage::OK
			);
		}
	}

	/**
	 * Migrates old itemfield table to new itemfield table
	 *
	 * @return void
	 */
	protected function migrateItemFieldTable() {
		// stop if prior migration haven't finished
		if ( !($this->finishState['field'] && $this->finishState['blob']) ) {
			return;
		}

		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_itemfield';
		$targetTable = 'tx_' . $this->extensionKey . '_domain_model_itemfield';
		$propertyMap = array(
			'pid' => 'pid',
			'item_id' => 'item',
			'fieldname' => 'field',
			'fieldvalue' => 'field_value',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'deleted' => 'deleted',
			'hidden' => 'hidden'
		);

		// attempt migration
		try {
			$countRecords = $this->databaseService->migrateTableData($sourceTable, $targetTable, $propertyMap, 30000);
			$this->addFlashMessage(
				sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
				'',
				FlashMessage::OK
			);
		} catch (NoData $e) {
			// no data to migrate
			$this->addFlashMessage($e->getMessage(), '', FlashMessage::INFO);
			$this->finishState['itemfield'] = TRUE;
		}
	}

	/**
	 * Migrates old item table to new item table
	 *
	 * @return void
	 */
	protected function migrateItemTable() {
		// stop if prior migrations haven't finished
		if ( !($this->finishState['blob'] && $this->finishState['itemtype'] && $this->finishState['itemfield'] && $this->finishState['item_item_mm'] && $this->finishState['item_xml_mm']) ) {
			return;
		}

		// @TODO ___reference in filehash table? Or are those BLOB-only? find out and see if we need to do things here or in migrateBlobs() or both!
		$uidMap = array();
		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_item';
		$targetTable = 'tx_' . $this->extensionKey . '_domain_model_item';
		// @TODO ___make note of this in docs:
		// this will go horribly wrong if $targetTable contained records before migration
		$referenceTables = array(
			'tx_' . $this->extensionKey . '_domain_model_itemfield' => 'item',
			'tx_' . $this->extensionKey . '_domain_model_itemblob' => 'item',
			'tx_' . $this->extensionKey . '_item_item_mm' => array(
				'uid_local',
				'uid_foreign',
			),
			'tx_' . $this->extensionKey . '_item_import_mm' => 'uid_local',
		);
		$propertyMap = array(
			'pid' => 'pid',
			'itemkey' => 'item_key',
			'itemtype' => 'item_type',
			'itemxml' => 'import',
			'item_parent' => 'parent_item',
			'item_child' => 'child_item',
			'itemfield' => 'item_field',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'deleted' => 'deleted',
			'hidden' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		);

		// attempt migration
		try {
			$countRecords = $this->databaseService->migrateTableData($sourceTable, $targetTable, $propertyMap, 150, $uidMap);
			$message = array(
				sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords)
			);
			foreach ($referenceTables as $referenceTable => $propertyArray) {
				if (!is_array($propertyArray)) {
					$propertyArray = array($propertyArray);
				}
				$countReferences = 0;
				foreach ($propertyArray as $property) {
					$countReferences += $this->databaseService->updatePropertyBySourceValue($referenceTable, $property, $uidMap, TRUE);
				}
				$message[] = sprintf($this->lang['referenceUpdate'], $referenceTable, $countReferences);
			}
			$this->addFlashMessage(join(' ', $message), '', FlashMessage::OK);
		} catch (NoData $e) {
			// no data to migrate
			$this->addFlashMessage($e->getMessage(), '', FlashMessage::INFO);
			$this->finishState['item'] = TRUE;
		}
	}

	/**
	 * Migrates old xml table to new xml table
	 *
	 * @return void
	 */
	protected function migrateXmlTable() {
		// stop if prior migrations haven't finished
		if ( !($this->finishState['fal_itemxml'] && $this->finishState['itemxml_filedirpath'] && $this->finishState['item_xml_mm']) ) {
			return;
		}

		// @TODO ___reference in preprocess mm table?
		// @TODO ___reference in importrule table?
		$uidMap = array();
		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_itemxml';
		$targetTable = 'tx_' . $this->extensionKey . '_domain_model_import';
		$referenceTables = array(
			'tx_' . $this->extensionKey . '_item_import_mm' => 'uid_foreign',
		);
		$propertyMap = array(
			'pid' => 'pid',
			'name' => 'title',
			'forget' => 'forget_on_update',
			'md5hash' => 'hash',
			'xmlpath' => 'file',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'deleted' => 'deleted',
			'hidden' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		);

		// attempt migration
		try {
			$countRecords = $this->databaseService->migrateTableData($sourceTable, $targetTable, $propertyMap, 300, $uidMap);
			$message = array(
				sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords)
			);
			foreach ($referenceTables as $referenceTable => $property) {
				$countReferences = $this->databaseService->updatePropertyBySourceValue($referenceTable, $property, $uidMap, TRUE);
				$message[] = sprintf($this->lang['referenceUpdate'], $referenceTable, $countReferences);
			}
			$this->addFlashMessage(join(' ', $message), '', FlashMessage::OK);
		} catch (NoData $e) {
			// no data to migrate
			$this->addFlashMessage($e->getMessage(), '', FlashMessage::INFO);
			$this->finishState['itemxml'] = TRUE;
		}
	}

	/**
	 * Migrate file-references from xml table to FAL.
	 *
	 * @return void
	 */
	protected function migrateXmlToFal() {
		$table = 'tx_' . $this->sourceExtensionKey . '_itemxml';
		$evaluation = 'xmlpath NOT RLIKE \'^[0-9]+$\'';

		$count = 0;
		// @LOW ___consider that using the import repository for this would get file references to register correctly (assumption)
		$toMigrate = $this->databaseService->selectTableRecords($table, $evaluation);
		// no results means we're done migrating
		if (empty($toMigrate)) {
			$this->addFlashMessage(
				sprintf($this->lang['nNothing'], 'XML files', 'migrate to FAL'),
				'',
				FlashMessage::INFO
			);
			$this->finishState['fal_itemxml'] = TRUE;
			return;
		}

		foreach ($toMigrate as $uid => $row) {
			if (isset($row['xmlpath'])) {
				// migrate import file to FAL and set the reference
				$errorMessage = NULL;
				try {
					$file = $this->fileService->retrieveFileObjectByPath($row['xmlpath']);
					$this->databaseService->updateTableRecords(
						$table,
						array(
							'xmlpath' => $file->getUid()
						),
						array(
							'uid' => $uid
						)
					);
					$count++;
				} catch (ResourceDoesNotExistException $e) {
					$errorMessage = $e->getMessage();
				} catch (FileException $e) {
					$errorMessage = $e->getMessage();
				}

				if ($errorMessage !== NULL) {
					$this->addFlashMessage(
						$errorMessage . ' ' . $this->lang['falMigrateFail'] . ' ' . sprintf(
							$this->lang['falMigrateFailCorrect'],
							$this->fileService->getDefaultStorage()->getName(),
							$table
						),
						sprintf($this->lang['falMigrateFailTitle'], 'Import', $row['name']),
						FlashMessage::ERROR
					);
				}
			}
		}
		if ($count > 0) {
			$this->addFlashMessage(
				sprintf($this->lang['falMigrateSuccess'], $table, $count),
				'',
				FlashMessage::OK
			);
		}
	}

	/**
	 * Checks filedirpaths for anything no longer supported:
	 * - anything other than the xml dir path
	 *
	 * @return void
	 */
	protected function migrateXmlFileDir() {
		// stop if prior migrations haven't finished
		if ( !$this->finishState['fal_itemxml'] ) {
			return;
		}

		$table = 'tx_' . $this->sourceExtensionKey . '_itemxml';
		$evaluation = 'filedirpath != \'\'';
		$xmlArray = $this->databaseService->selectTableRecords($table, $evaluation);

		$successCount = 0;
		$errorCount = 0;
		foreach ($xmlArray as $uid => $xml) {
			// get all the necessary paths
			$file = $this->fileService->getFileObjectByUid($xml['xmlpath']);
			$xmlPath = pathinfo($file->getPublicUrl());
			$xmlDirPath = PATH_site . $xmlPath['dirname'] . '/';
			$fileDirPath = rtrim($xml['filedirpath'], '/') . '/';

			if ($xmlDirPath !== $fileDirPath) {
				$sourceFileDir = $fileDirPath . $xmlPath['filename'] . '/';
				$targetFileDir = $xmlDirPath . $xmlPath['filename'] . '/';

				// if we get here, the filedir was moved to the correct location, in which case we can automatically fix the filedirpath
				if (file_exists($targetFileDir)) {
					$this->databaseService->updateTableRecords(
						$table,
						array(
							// empty means processed, since we're losing the property anyway
							'filedirpath' => ''
						),
						array(
							'uid' => $uid
						)
					);
					$successCount++;
					continue;
				}
				// if the assumed sourceFileDir exists, we can suggest where to move it next so that this method can fix the issue, but
				// we will NOT move files automatically, since the reason for its current location may be related to available harddisk space
				$advice = file_exists($sourceFileDir)
					? sprintf(
						$this->lang['dirMoveAutomatic'],
						$sourceFileDir,
						$targetFileDir
					)
					// otherwise, we can only suggest a fully manual solution..
					: sprintf(
						$this->lang['dirMoveManual'],
						$sourceFileDir,
						$table
					);

				$this->addFlashMessage(
					sprintf(
						$this->lang['dirMismatch'],
						$fileDirPath,
						$xmlDirPath,
						$advice
					),
					'',
					FlashMessage::ERROR
				);
				$errorCount++;
			}
		}

		if ($successCount > 0) {
			$this->addFlashMessage(
				sprintf(
					$this->lang['migrateSuccess'],
					$table . '.filedirpath',
					$successCount
				),
				'',
				FlashMessage::OK
			);
		} elseif ($errorCount <= 0) {
			// no results means we're done migrating
			$this->addFlashMessage(
				sprintf($this->lang['nNothing'], 'XML filedirpaths', 'correct'),
				'',
				FlashMessage::INFO
			);
			$this->finishState['itemxml_filedirpath'] = TRUE;
		}
	}

	/**
	 * Checks the existence of profiles that have not yet been
	 * re-imported into the new structure.
	 *
	 * @return void
	 */
	protected function checkProfiles() {
		// stop if prior migrations haven't finished --ARTIFICIAL
		if ( !$this->finishState['item'] ) {
			return;
		}

		$sourceTable = 'tx_' . $this->sourceExtensionKey . '_itemprofile';
		$targetTable = 'tx_' . $this->extensionKey . '_domain_model_profile';
		$where = '';
		// select without limit
		$existingProfiles = $this->databaseService->selectTableRecords($targetTable, '', '*', '');
		if (!empty($existingProfiles)) {
			$profileKeys = array();
			foreach ($existingProfiles as $profile) {
				$profileKeys[] = $profile['profile_key'];
			}
			$where = 'profilekey NOT IN (\'' . join('\',\'', $profileKeys) . '\')';
		}

		$unmatchedProfiles = $this->databaseService->selectTableRecords($sourceTable, $where);
		if (!empty($unmatchedProfiles)) {
			$profiles = array();
			foreach ($unmatchedProfiles as $profile) {
				$profiles[] = $profile['name'];
			}
			$this->addFlashMessage(
				sprintf(
					$this->lang['migrateManual'],
					'profiles',
					$this->lang['profileImport'],
					'\'' . join('\', \'', $profiles) . '\''
				),
				sprintf($this->lang['migrateManualTitle'], 'profiles'),
				FlashMessage::WARNING
			);
		} else {
			$this->addFlashMessage(
				sprintf($this->lang['nNothing'], 'profiles', 'migrate'),
				'',
				FlashMessage::INFO
			);
			$this->finishState['profiles'] = TRUE;
		}
	}

	/**
	 * Checks the existence of original pi1-plugins.
	 *
	 * @return void
	 */
	protected function checkPlugins() {
		// stop if prior migrations haven't finished --ARTIFICIAL
		if ( !$this->finishState['item'] ) {
			return;
		}

		$pluginListType = $this->sourceExtensionKey . '_pi1';
		$table = 'tt_content';
		$where = implode(' ' . DatabaseConnection::AND_Constraint . ' ', array(
			'deleted=0',
			'CType=\'list\'',
			'list_type=\'' . $pluginListType . '\''
		));

		$existingPlugins = $this->databaseService->selectTableRecords($table, $where);
		if (!empty($existingPlugins)) {
			$plugins = array();
			foreach ($existingPlugins as $plugin) {
				// ugly hack which gives us easy insight in the flexform values per plugin
				$plugins[] = '<a href="#" onclick="(function() {jQuery(\'.hidden-info-plugin-text-' . (int) $plugin['uid'] . '\').slideToggle();})();" >' .
					'page:' . (int) $plugin['pid'] . '|content:' . (int) $plugin['uid'] . '|' . htmlspecialchars($plugin['header']) . '</a>' .
					'<pre class="hidden-info-plugin-text-' . (int) $plugin['uid'] . '" style="display:none;background-color:white;border:1px solid #aaa;">' .
					htmlspecialchars($plugin['pi_flexform']) . '</pre>';
			}
			$this->addFlashMessage(
				sprintf(
					$this->lang['migrateManual'],
					'plugins',
					$this->lang['pluginCreate'],
					join(', ', $plugins)
				),
				sprintf($this->lang['migrateManualTitle'], 'plugins'),
				FlashMessage::WARNING
			);
		} else {
			$this->addFlashMessage(
				sprintf($this->lang['nNothing'], 'outdated plugins', 're-create'),
				'',
				FlashMessage::INFO
			);
			$this->finishState['plugins'] = TRUE;
		}
	}

}
