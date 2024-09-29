<?php

namespace Innologi\Decosdata\Command\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\TYPO3ExtUpdate\ExtUpdateAbstract;
use Innologi\TYPO3ExtUpdate\Service\Exception\FileException;
use Innologi\TYPO3ExtUpdate\Service\Exception\NoData;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Migrate Controller
 *
 * We're basing it of our ExtUpdate lib originally intended for
 * ext_update classes, but this works just as well.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class MigrateController extends ExtUpdateAbstract
{
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
    protected $sourceExtensionVersion = '6.2.0';

    /**
     * Language labels => messages
     *
     * @var array
     */
    protected $lang = [
        // @TODO ___make an entry in the manual explaining how to protect files against unauthorized access
        'dirMismatch' => 'The document path \'%1$s\' is different from the XML Import path \'%2$s\'. This is no longer supported. If this was applied for security-reasons, read the corresponding entry in the manual for alternatives. %3$s',
        'dirMoveAutomatic' => 'Please move, copy or symlink the file-directory \'%1$s\' so that it is available at \'%2$s\'. The updater will automatically detect this at the next run.',
        'dirMoveManual' => 'Unfortunately, the updater cannot detect the corresponding file-directory in the document path. (assumed to be \'%1$s\') Please move, copy or symlink it so that it becomes available within the XML Import path, then change the \'%2$s\' record manually to reflect this.',
        'falMigrateSuccess' => 'Migrated %2$d \'%1$s\' records to FAL.',
        'falMigrateFail' => 'Cannot migrate file to FAL.',
        'falMigrateFailCorrect' => 'You should correct this issue. You can move the file to a location within e.g. \'%1$s\' and will have to correct the path within the \'%2$s\' record.',
        'falMigrateFailDelete' => 'The file registration will hence not be migrated.',
        'falMigrateFailTitle' => '%1$s FAL migration: %2$s',
        'migrateManual' => 'Due to necessary core changes between extensions, an automated migration of %1$s is not possible. %2$s: %3$s',
        'migrateManualTitle' => 'Can not automatically migrate %1$s',
        'migrateSuccess' => 'Migrated %2$d \'%1$s\' records.',
        'migrateWait' => 'Prerequisites for \'%1$s\' record migration not yet met.',
        'pluginCreate' => 'It is recommended to create new plugins and delete the old ones once succesfully re-created. Only then will they disappear from the following list',
        'profileImport' => 'You will need to re-import the profiles if you wish to use them. The following profiles were found to be not yet re-imported',
    ];

    /**
     * Registers which migrations are finished, so that migrations
     * with prerequisites can determine if they can start.
     *
     * @var array
     */
    protected $finishState = [
        'blob' => false,
        'fal_blob' => false,
        'fal_itemxml' => false,
        'item' => false,
        'item_item_mm' => false,
        'item_xml_mm' => false,
        'itemfield' => false,
        'itemxml' => false,
        'itemxml_filedirpath' => false,
        //'plugins' => FALSE,
        //'profiles' => FALSE,
    ];
    // @LOW can we add x "of Y" migrated records to messages? Helps to show progress
    /**
     * Provides the methods to be executed during update.
     *
     * @return boolean TRUE on complete, FALSE on incomplete
     */
    public function processUpdates(): bool
    {
        // the order is important for some of these!
        // start with the things that can go wrong and should be corrected first
        $this->migrateXmlToFal();
        $this->migrateXmlFileDir();
        $this->migrateXmlTable();
        // blob fal file registration
        $this->migrateBlobToFal();
        // core data: item/blob/itemfield
        $this->migrateItemTable();
        $this->migrateBlobs();
        $this->migrateItemFieldTable();
        // mm tables
        $this->migrateItemItemMmTable();
        $this->migrateItemXmlMmTable();
        // do some checks that give warnings/instructions for some things
        //$this->checkProfiles();
        //$this->checkPlugins();

        // if even one finishState is FALSE, we're not finished
        return !in_array(false, $this->finishState, true);
        // @TODO ___Task Migration ('auto' property)
    }



    /**
     * Migrate file-references from xml table to FAL.
     */
    protected function migrateXmlToFal()
    {
        $table = 'tx_' . $this->sourceExtensionKey . '_itemxml';
        $where = 'migrated_uid = 0 AND migrated_file = 0';
        $max = $this->databaseService->countTableRecords($table, $where);

        // no results means we're done migrating
        if ($max === 0) {
            $this->finishState['fal_itemxml'] = true;
            return;
        }

        $this->io->text('Migrate file-references from xml table to FAL.');
        $this->io->progressStart($max);

        $count = 0;
        $errorCount = 0;
        while ($count + $errorCount < $max) {
            $toMigrate = $this->databaseService->selectTableRecords($table, $where, '*', 50);
            $step = 0;
            foreach ($toMigrate as $uid => $row) {
                if (isset($row['xmlpath'])) {
                    // migrate import file to FAL and set the reference
                    try {
                        $file = $this->fileService->retrieveFileObjectByPath($row['xmlpath']);
                        $this->databaseService->updateTableRecords(
                            $table,
                            [
                                'migrated_file' => $file->getUid(),
                            ],
                            [
                                'uid' => $uid,
                            ],
                        );
                        $step++;
                    } catch (FileException $e) {
                        $this->io->newLine(2);
                        $this->addMessage(
                            $e->getFormattedErrorMessage() . ' ' . $this->lang['falMigrateFail'] . ' ' . sprintf(
                                $this->lang['falMigrateFailCorrect'],
                                $this->fileService->getDefaultStorage()->getName(),
                                $table,
                            ),
                            sprintf($this->lang['falMigrateFailTitle'], 'Import', $row['name']),
                            ContextualFeedbackSeverity::ERROR,
                        );
                        $errorCount++;
                    }
                }
            }

            $count += $step;
            $this->io->progressAdvance($step);
        }

        if ($count > 0) {
            $this->io->newLine(2);
            $this->addMessage(
                sprintf($this->lang['falMigrateSuccess'], $table, $count),
                '',
                ContextualFeedbackSeverity::OK,
            );
        }

        if ($errorCount === 0) {
            $this->finishState['fal_itemxml'] = true;
            return;
        }
    }

    /**
     * Checks filedirpaths for anything no longer supported:
     * - anything other than the xml dir path
     */
    protected function migrateXmlFileDir()
    {
        // stop if prior migrations haven't finished
        if (!$this->finishState['fal_itemxml']) {
            return;
        }

        $table = 'tx_' . $this->sourceExtensionKey . '_itemxml';
        $where = 'migrated_uid = 0 AND migrated_filedir = 0 AND migrated_file > 0';
        $max = $this->databaseService->countTableRecords($table, $where);

        // no results means we're done migrating
        if ($max === 0) {
            $this->finishState['itemxml_filedirpath'] = true;
            return;
        }

        $this->io->text('Migrate XML filedirpaths.');
        $this->io->progressStart($max);

        $sitePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
        $count = 0;
        $errorCount = 0;
        while ($count + $errorCount < $max) {
            $okUidArray = [];
            $xmlArray = $this->databaseService->selectTableRecords($table, $where, '*', 50);
            foreach ($xmlArray as $uid => $xml) {
                // get all the necessary paths
                $file = $this->fileService->getFileObjectByUid($xml['migrated_file']);
                $xmlPath = pathinfo($file->getPublicUrl());
                $xmlDirPath = $sitePath . $xmlPath['dirname'] . '/';
                $fileDirPath = rtrim((string) $xml['filedirpath'], '/') . '/';

                if ($xmlDirPath === $fileDirPath) {
                    $okUidArray[] = $uid;
                } else {
                    $sourceFileDir = $fileDirPath . $xmlPath['filename'] . '/';
                    $targetFileDir = $xmlDirPath . $xmlPath['filename'] . '/';

                    if (file_exists($targetFileDir)) {
                        // if we get here, the filedir was moved to the correct location, in which case we can automatically fix the filedirpath
                        $okUidArray[] = $uid;
                    } else {
                        // if the assumed sourceFileDir exists, we can suggest where to move it next so that this method can fix the issue, but
                        // we will NOT move files automatically, since the reason for its current location may be related to available harddisk space
                        $advice = file_exists($sourceFileDir)
                            ? sprintf(
                                $this->lang['dirMoveAutomatic'],
                                $sourceFileDir,
                                $targetFileDir,
                            )
                            // otherwise, we can only suggest a fully manual solution..
                            : sprintf(
                                $this->lang['dirMoveManual'],
                                $sourceFileDir,
                                $table,
                            );

                        $this->io->newLine(2);
                        $this->addMessage(
                            sprintf(
                                $this->lang['dirMismatch'],
                                $fileDirPath,
                                $xmlDirPath,
                                $advice,
                            ),
                            '',
                            ContextualFeedbackSeverity::ERROR,
                        );
                        $errorCount++;
                    }
                }
            }

            if (!empty($okUidArray)) {
                $step = count($okUidArray);
                $count += $step;
                $this->io->progressAdvance($step);

                // any xml found ok will be marked as such
                foreach ($okUidArray as $uid) {
                    $this->databaseService->updateTableRecords(
                        $table,
                        [
                            'migrated_filedir' => 1,
                        ],
                        [
                            'uid' => $uid,
                        ],
                    );
                }
            }
        }

        if ($count > 0) {
            $this->io->newLine(2);
            $this->addMessage(
                sprintf(
                    $this->lang['migrateSuccess'],
                    $table . '.filedirpath',
                    $count,
                ),
                '',
                ContextualFeedbackSeverity::OK,
            );
        }

        if ($errorCount <= 0) {
            // no results means we're done migrating
            $this->finishState['itemxml_filedirpath'] = true;
        }
    }

    /**
     * Migrates old xml table to new xml table
     */
    protected function migrateXmlTable()
    {
        // stop if prior migrations haven't finished
        if (!($this->finishState['fal_itemxml'] && $this->finishState['itemxml_filedirpath'])) {
            return;
        }

        // @TODO ___reference in preprocess mm table?
        // @TODO ___reference in importrule table?
        $sourceTable = 'tx_' . $this->sourceExtensionKey . '_itemxml';
        $targetTable = 'tx_' . $this->extensionKey . '_domain_model_import';
        $propertyMap = [
            'pid' => 'pid',
            'name' => 'title',
            'migrated_file' => [
                'fileReference' => [
                    'targetProperty' => 'file',
                ],
            ],
            'forget' => 'forget_on_update',
            'md5hash' => 'hash',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'deleted' => 'deleted',
            'hidden' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ];
        $evaluation = [
            'migrated_file > 0',
        ];

        // attempt migration
        try {
            $countRecords = $this->databaseService->migrateTableDataWithReferenceUid($sourceTable, $targetTable, $propertyMap, 'migrated_uid', $evaluation, 50, $this->io);
            $this->io->newLine(2);
            $this->addMessage(
                sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
                '',
                ContextualFeedbackSeverity::OK,
            );
        } catch (NoData) {
            // do nothing
        }

        $this->finishState['itemxml'] = true;
    }

    /**
     * Migrate file-references from BLOB to FAL.
     */
    protected function migrateBlobToFal()
    {
        // stop if prior migrations haven't finished --ARTIFICIAL
        if (!$this->finishState['itemxml']) {
            return;
        }

        $table = 'tx_' . $this->sourceExtensionKey . '_itemfield';
        $refTable = 'tx_' . $this->sourceExtensionKey . '_item';
        // filepath used to be a concatted string
        $select = 'it.uid AS uid,CONCAT(itx.filedirpath,itf.fieldvalue) AS filepath';
        $from = $refTable . ' it
			INNER JOIN (' . $table . ' itf)
				ON (it.uid=itf.item_id)
			LEFT JOIN (tx_decospublisher_item_itemxml_mm itxr,tx_decospublisher_itemxml itx)
				ON (itf.item_id=itxr.uid_local
					AND itxr.uid_foreign=itx.uid AND itxr.current=1)';
        $where = 'it.no_migrate = 0 AND it.migrated_uid = 0 AND it.migrated_file = 0 AND itf.fieldname = \'FILEPATH\'';

        $max = $this->databaseService->countTableRecords($from, $where);

        // no results means we're done migrating
        if ($max === 0) {
            $this->finishState['fal_blob'] = true;
            return;
        }

        $this->io->text('Migrate file-references from BLOB to FAL.');
        $this->io->progressStart($max);

        $count = 0;
        $errorCount = 0;
        while ($count + $errorCount < $max) {
            $step = 0;
            $updateValues = [];
            $toMigrate = $this->databaseService->selectTableRecords($from, $where, $select, 25);
            foreach ($toMigrate as $uid => $row) {
                // migrate itemfield file to FAL and set the reference
                try {
                    $file = $this->fileService->retrieveFileObjectByPath($row['filepath']);
                    $updateValues = [
                        'migrated_file' => $file->getUid(),
                    ];
                    $step++;
                } catch (FileException $e) {
                    $this->io->newLine(2);
                    $this->addMessage(
                        $e->getFormattedErrorMessage() . ' ' . $this->lang['falMigrateFail'] . ' ' . $this->lang['falMigrateFailDelete'],
                        sprintf($this->lang['falMigrateFailTitle'], 'Itemfield', 'id ' . $uid),
                        ContextualFeedbackSeverity::WARNING,
                    );
                    $updateValues = [
                        'no_migrate' => 1,
                    ];
                    $errorCount++;
                }

                $this->databaseService->updateTableRecords(
                    $refTable,
                    $updateValues,
                    [
                        'uid' => $uid,
                    ],
                );
            }
            $count += $step;
            $this->io->progressAdvance($step);
        }

        if ($count > 0) {
            $this->io->newLine(2);
            $this->addMessage(
                sprintf($this->lang['falMigrateSuccess'], $table, $count),
                '',
                ContextualFeedbackSeverity::OK,
            );
        }

        if ($errorCount <= 0) {
            // no errorresults means we're done migrating
            $this->finishState['fal_blob'] = true;
        }
    }

    /**
     * Migrates old item table to new item table
     */
    protected function migrateItemTable()
    {
        // stop if prior migrations haven't finished --ARTIFICIAL
        if (!($this->finishState['fal_blob'])) {
            return;
        }

        // @TODO ___reference in filehash table? Or are those BLOB-only? find out and see if we need to do things here or in migrateBlobs() or both!
        $sourceTable = 'tx_' . $this->sourceExtensionKey . '_item';
        $targetTable = 'tx_' . $this->extensionKey . '_domain_model_item';
        $propertyMap = [
            'pid' => 'pid',
            'itemkey' => 'item_key',
            'itemtype' => [
                'valueReference' => [
                    'targetProperty' => 'item_type',
                    'foreignTable' => 'tx_' . $this->extensionKey . '_domain_model_itemtype',
                    'foreignField' => 'uid',
                    'valueField' => 'item_type',
                    'uniqueBy' => ['pid'],
                ],
            ],
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
            'endtime' => 'endtime',
        ];
        $evaluation = [
            'itemtype != \'BLOB\'',
            'no_migrate = 0',
        ];

        // attempt migration
        try {
            $countRecords = $this->databaseService->migrateTableDataWithReferenceUid($sourceTable, $targetTable, $propertyMap, 'migrated_uid', $evaluation, 100, $this->io);
            $this->io->newLine(2);
            $this->addMessage(
                sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
                '',
                ContextualFeedbackSeverity::OK,
            );
        } catch (NoData) {
            // do nothing
        }

        $this->finishState['item'] = true;
    }


    /**
     * Migrate BLOB items to itemblob table.
     */
    protected function migrateBlobs()
    {
        // stop if prior migrations haven't finished
        if (!($this->finishState['fal_blob'] && $this->finishState['item'])) {
            return;
        }

        $sourceTable = 'tx_' . $this->sourceExtensionKey . '_item';
        $sourceDataTable = 'tx_' . $this->sourceExtensionKey . '_itemfield';
        $sourceMmTable = 'tx_' . $this->sourceExtensionKey . '_item_mm';
        $targetTable = 'tx_' . $this->extensionKey . '_domain_model_itemblob';
        $propertyMap = [
            'pid' => 'pid',
            'item_key' => 'item_key',
            'sequence' => 'sequence',
            //'file' => 'file',
            'item' => 'item',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'deleted' => 'deleted',
            'hidden' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ];

        // query to get all BLOB item records in correct order with all relevant properties attached
        $select = 'it.pid AS pid,it.itemkey AS item_key,itf1.fieldvalue AS sequence,
			it.migrated_file AS file,it2.migrated_uid AS item,it.tstamp AS tstamp,
			it.crdate AS crdate,it.cruser_id AS cruser_id,it.deleted AS deleted,
			it.hidden AS hidden,it.starttime AS starttime,it.endtime AS endtime,
			it.uid AS uid,UNIX_TIMESTAMP(itf2.fieldvalue) AS docdate';
        $from = sprintf(
            '%1$s it
			LEFT JOIN %2$s itf1 ON (it.uid = itf1.item_id AND itf1.fieldname=\'SEQUENCE\')
			LEFT JOIN %2$s itf2 ON (it.uid = itf2.item_id AND itf2.fieldname=\'DOCUMENT_DATE\')
			LEFT JOIN (%3$s itr,%1$s it2) ON (it.uid = itr.uid_local AND itr.uid_foreign = it2.uid)',
            $sourceTable,
            $sourceDataTable,
            $sourceMmTable,
        );
        $where = 'it.itemtype = \'BLOB\'
			AND it.migrated_uid = 0 AND it.no_migrate = 0
			AND it2.migrated_uid > 0';
        // note that docdate is only used for extra sorting, if sequence is not provided
        $orderBy = 'item ASC,sequence ASC,docdate ASC,uid ASC';

        $max = $this->databaseService->countTableRecords($from, $where);

        // no results means we're done migrating
        if ($max === 0) {
            $this->finishState['blob'] = true;
            return;
        }

        $this->io->text('Migrate BLOB items to itemblob table.');
        $this->io->progressStart($max);

        $count = 0;
        $parentItem = 0;
        $sequence = 0;
        while ($count < $max) {
            $toMigrate = $this->databaseService->selectTableRecords($from, $where, $select, 5, $orderBy);

            // first check for:
            // - file id's not set to <1, we skip these from insert but not from update
            // - fields that aren't in propertyMap
            // - missing sequences and provide them if necessary
            $toInsert = [];
            $remainder = [];
            $fileUid = [];
            foreach ($toMigrate as $uid => $row) {
                if ((int) $row['file'] > 0) {
                    $fileUid[$uid] = (int) $row['file'];
                    // it's a bit hacky, but meh, it's just a few lines in an update script
                    unset($row['file']);
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

                    $toInsert[$uid] = $row;
                } else {
                    // these are registered to flag as no_migrate = 1
                    $remainder[] = $uid;
                }
            }

            if (!empty($toInsert)) {
                // insert!
                $this->databaseService->insertTableRecords(
                    $targetTable,
                    $propertyMap,
                    $toInsert,
                );
                // not nice, but it's a very specific part of an update script, I really don't care
                // get first insert ID
                // @extensionScannerIgnoreLine TYPO3_DB-usage needs a rewrite anyway once this ext goes standalone
                $i = $GLOBALS['TYPO3_DB']->sql_insert_id();
                foreach ($toInsert as $uid => $row) {
                    // update migrated_uid for inserted records
                    $this->databaseService->updateTableRecords($sourceTable, [
                        'migrated_uid' => $i,
                    ], [
                        'uid' => $uid,
                    ]);
                    // create the file reference
                    $this->fileService->setFileReference($fileUid[$uid], $targetTable, $i++, 'file', (int) $row['pid']);
                    // for every $toInsert, there is a matching $fileUid, so no need for a condition
                }
            }

            if (!empty($remainder)) {
                // set no_migrate to 1 for records that did not meet criteria
                $this->databaseService->updateTableRecords(
                    $sourceTable,
                    [
                        'no_migrate' => 1,
                    ],
                    [
                        'uid' => [
                            'operator' => ' IN (%1$s)',
                            'no_quote' => true,
                            'value' => '\'' . join('\',\'', $remainder) . '\'',
                        ],
                    ],
                );
            }

            // set no_migrate to 1 for all itemfields of these records
            $this->databaseService->updateTableRecords(
                $sourceDataTable,
                [
                    'no_migrate' => 1,
                ],
                [
                    'item_id' => [
                        'operator' => ' IN (%1$s)',
                        'no_quote' => true,
                        'value' => '\'' . join('\',\'', array_keys($toMigrate)) . '\'',
                    ],
                ],
            );

            $steps = count($toMigrate);
            $count += $steps;
            $this->io->progressAdvance($steps);
        }

        $this->io->newLine(2);
        $this->addMessage(
            sprintf($this->lang['migrateSuccess'], $targetTable, $count),
            '',
            ContextualFeedbackSeverity::OK,
        );

        $this->finishState['blob'] = true;
    }

    /**
     * Migrates old itemfield table to new itemfield table
     */
    protected function migrateItemFieldTable()
    {
        // stop if prior migration haven't finished
        if (!($this->finishState['item'] && $this->finishState['blob'])) {
            return;
        }

        $sourceTable = 'tx_' . $this->sourceExtensionKey . '_itemfield';
        $targetTable = 'tx_' . $this->extensionKey . '_domain_model_itemfield';
        $propertyMap = [
            'pid' => 'pid',
            'item_id' => [
                'valueReference' => [
                    'targetProperty' => 'item',
                    'foreignTable' => 'tx_' . $this->sourceExtensionKey . '_item',
                    'foreignField' => 'migrated_uid',
                    'valueField' => 'uid',
                ],
            ],
            'fieldname' => [
                'valueReference' => [
                    'targetProperty' => 'field',
                    'foreignTable' => 'tx_' . $this->extensionKey . '_domain_model_field',
                    'foreignField' => 'uid',
                    'valueField' => 'field_name',
                    'uniqueBy' => ['pid'],
                ],
            ],
            'fieldvalue' => 'field_value',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'cruser_id' => 'cruser_id',
            'deleted' => 'deleted',
            'hidden' => 'hidden',
        ];
        $evaluation = [
            'no_migrate = 0',
            // exclude empty values, since we no longer import those in decosdata
            'fieldvalue != \'\'',
        ];

        // attempt migration
        try {
            $countRecords = $this->databaseService->migrateTableDataWithReferenceUid($sourceTable, $targetTable, $propertyMap, 'migrated_uid', $evaluation, 100, $this->io);
            $this->io->newLine(2);
            $this->addMessage(
                sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
                '',
                ContextualFeedbackSeverity::OK,
            );
        } catch (NoData) {
            // do nothing
        }

        $this->finishState['itemfield'] = true;
    }

    /**
     * Migrates old item-mm table to new item-mm table
     */
    protected function migrateItemItemMmTable()
    {
        // stop if prior migration haven't finished --itemfield is ARTIFICIAL
        if (!($this->finishState['item'] && $this->finishState['blob'] && $this->finishState['itemfield'])) {
            return;
        }

        $sourceTable = 'tx_' . $this->sourceExtensionKey . '_item_mm';
        $targetTable = 'tx_' . $this->extensionKey . '_item_item_mm';
        $localConfig = [
            'table' => 'tx_' . $this->sourceExtensionKey . '_item',
            'uid' => 'migrated_uid',
            'evaluation' => ['itemtype != \'BLOB\''],
        ];
        $foreignConfig = $localConfig;
        $propertyMap = [
            'sorting' => 'sorting',
            'sorting_foreign' => 'sorting_foreign',
        ];

        // attempt migration
        try {
            $countRecords = $this->databaseService->migrateMmTableWithReferenceUid($sourceTable, $targetTable, $localConfig, $foreignConfig, $propertyMap, 'migrated', 100, $this->io);
            $this->io->newLine(2);
            $this->addMessage(
                sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
                '',
                ContextualFeedbackSeverity::OK,
            );
        } catch (NoData) {
            // do nothing
        }

        $this->finishState['item_item_mm'] = true;
    }

    /**
     * Migrates old item-xml-mm table to new item-xml-mm table
     */
    protected function migrateItemXmlMmTable()
    {
        /// stop if prior migration haven't finished --item_item_mm is ARTIFICIAL
        if (!($this->finishState['item'] && $this->finishState['itemxml'] && $this->finishState['item_item_mm'])) {
            return;
        }

        $sourceTable = 'tx_' . $this->sourceExtensionKey . '_item_itemxml_mm';
        $targetTable = 'tx_' . $this->extensionKey . '_item_import_mm';
        $localConfig = [
            'table' => 'tx_' . $this->sourceExtensionKey . '_item',
            'uid' => 'migrated_uid',
            'evaluation' => ['itemtype != \'BLOB\''],
        ];
        $foreignConfig = [
            'table' => 'tx_' . $this->sourceExtensionKey . '_itemxml',
            'uid' => 'migrated_uid',
        ];
        $propertyMap = [
            'sorting' => 'sorting',
        ];

        // attempt migration
        try {
            $countRecords = $this->databaseService->migrateMmTableWithReferenceUid($sourceTable, $targetTable, $localConfig, $foreignConfig, $propertyMap, 'migrated', 100, $this->io);
            $this->io->newLine(2);
            $this->addMessage(
                sprintf($this->lang['migrateSuccess'], $sourceTable, $countRecords),
                '',
                ContextualFeedbackSeverity::OK,
            );
        } catch (NoData) {
            // do nothing
        }

        $this->finishState['item_xml_mm'] = true;
    }

    /**
     * Checks the existence of profiles that have not yet been
     * re-imported into the new structure.
     */
    protected function checkProfiles()
    {
        // stop if prior migrations haven't finished --ARTIFICIAL
        if (!$this->finishState['item_xml_mm']) {
            return;
        }

        $sourceTable = 'tx_' . $this->sourceExtensionKey . '_itemprofile';
        $targetTable = 'tx_' . $this->extensionKey . '_domain_model_profile';
        $where = '';
        // select without limit
        $existingProfiles = $this->databaseService->selectTableRecords($targetTable, '', '*', '');
        if (!empty($existingProfiles)) {
            $profileKeys = [];
            foreach ($existingProfiles as $profile) {
                $profileKeys[] = $profile['profile_key'];
            }
            $where = 'profilekey NOT IN (\'' . join('\',\'', $profileKeys) . '\')';
        }

        $unmatchedProfiles = $this->databaseService->selectTableRecords($sourceTable, $where);
        if (!empty($unmatchedProfiles)) {
            $profiles = [];
            foreach ($unmatchedProfiles as $profile) {
                $profiles[] = $profile['name'];
            }
            $this->addMessage(
                sprintf(
                    $this->lang['migrateManual'],
                    'profiles',
                    $this->lang['profileImport'],
                    '\'' . join('\', \'', $profiles) . '\'',
                ),
                sprintf($this->lang['migrateManualTitle'], 'profiles'),
                ContextualFeedbackSeverity::WARNING,
            );
        } else {
            $this->finishState['profiles'] = true;
        }
    }

    /**
     * Checks the existence of original pi1-plugins.
     */
    protected function checkPlugins()
    {
        // stop if prior migrations haven't finished --ARTIFICIAL
        if (!$this->finishState['item_xml_mm']) {
            return;
        }

        $pluginListType = $this->sourceExtensionKey . '_pi1';
        $table = 'tt_content';
        $where = implode(' ' . DatabaseConnection::AND_Constraint . ' ', [
            'deleted=0',
            'CType=\'list\'',
            'list_type=\'' . $pluginListType . '\'',
        ]);

        $existingPlugins = $this->databaseService->selectTableRecords($table, $where);
        if (!empty($existingPlugins)) {
            $plugins = [];
            foreach ($existingPlugins as $plugin) {
                // ugly hack which gives us easy insight in the flexform values per plugin
                $plugins[] = '<a href="#" onclick="(function() {jQuery(\'.hidden-info-plugin-text-' . (int) $plugin['uid'] . '\').slideToggle();})();" >' .
                    'page:' . (int) $plugin['pid'] . '|content:' . (int) $plugin['uid'] . '|' . htmlspecialchars((string) $plugin['header']) . '</a>' .
                    '<pre class="hidden-info-plugin-text-' . (int) $plugin['uid'] . '" style="display:none;background-color:white;border:1px solid #aaa;">' .
                    htmlspecialchars((string) $plugin['pi_flexform']) . '</pre>';
            }
            $this->addMessage(
                sprintf(
                    $this->lang['migrateManual'],
                    'plugins',
                    $this->lang['pluginCreate'],
                    join(', ', $plugins),
                ),
                sprintf($this->lang['migrateManualTitle'], 'plugins'),
                ContextualFeedbackSeverity::WARNING,
            );
        } else {
            $this->finishState['plugins'] = true;
        }
    }
}
