<?php

namespace Innologi\Decosdata\Mvc\Domain;

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
use Innologi\Decosdata\Exception\StaticUidInsertion;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * General RepositoryAbstract class
 *
 * Extends original extbase repository and adds a few extras
 * this extension uses on several occassions.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class RepositoryAbstract extends Repository
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * Returns DatabaseConnection
     *
     * Using a method for this so we don't need to overrule the constructor.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        // @extensionScannerIgnoreLine TYPO3_DB-usage needs a rewrite anyway once this ext goes standalone
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Resolve the table name for the objectType
     *
     * @return string
     */
    protected function getTableName()
    {
        if ($this->tableName === null) {
            $parts = explode(
                '\\',
                ltrim($this->objectType, '\\'),
            );
            $this->tableName = 'tx_' . strtolower(
                implode(
                    '_',
                    // skip vendor
                    array_slice($parts, 1),
                ),
            );
        }
        return $this->tableName;
    }

    /**
     * Insert record data in repository table, and add the following fields to reference:
     * - uid
     * - pid (if not set)
     * - crdate
     * - tstamp
     *
     * Used to speed up persistence considerably of e.g. valueObjects.
     *
     * @throws \Innologi\Decosdata\Exception\StaticUidInsertion
     */
    public function insertRecord(array &$data): void
    {
        if (isset($data['uid'])) {
            throw new StaticUidInsertion(1448550380, [
                $this->getTableName(),
                $data['uid'],
            ]);
        }
        if (!isset($data['pid'])) {
            $data['pid'] = $this->getStoragePid();
        }
        // set initial time values
        $data['crdate'] = $data['tstamp'] = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        // insert
        $this->getDatabaseConnection()->exec_INSERTquery($this->getTableName(), $data);
        // @LOW ___what about SQL errors?
        $data['uid'] = $this->getDatabaseConnection()->sql_insert_id();
    }

    /**
     * Gets Storage Pid from configuration
     *
     * Used by custom database methods as a fallback. Note that this
     * is a slower alternative to providing the pid with the database
     * method directly.
     *
     * @return integer
     */
    protected function getStoragePid()
    {
        $frameworkConfiguration = GeneralUtility::makeInstance(
            ConfigurationManagerInterface::class,
        )->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
        );
        // @LOW ___this will fail completely if multiple are set
        return (int) $frameworkConfiguration['persistence']['storagePid'];
    }
}
