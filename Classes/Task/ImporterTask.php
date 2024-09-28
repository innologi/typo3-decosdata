<?php

namespace Innologi\Decosdata\Task;

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
use Innologi\Decosdata\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Importer Task
 *
 * Task-implementation of ImporterService
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImporterTask extends AbstractTask
{
    /**
     * @var string
     */
    protected $extensionName = 'Decosdata';

    /**
     * @var array
     */
    public $selectedImports = [];

    /**
     * Execute task logic
     *
     * @return boolean
     * @throws \Exception
     */
    public function execute()
    {
        $bootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        $bootstrap->initialize([
            'pluginName' => 'Importer',
            'extensionName' => $this->extensionName,
            'vendorName' => 'Innologi',
        ]);

        /** @var \Innologi\Decosdata\Service\Importer\ImporterService $importerService */
        $importerService = GeneralUtility::makeInstance(\Innologi\Decosdata\Service\Importer\ImporterService::class);
        $importerService->importUidSelection($this->selectedImports);

        // persist any lingering data
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager */
        $persistenceManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $persistenceManager->persistAll();

        $errors = $importerService->getErrors();
        if (!empty($errors)) {
            throw new \Exception(
                '<pre>' . LocalizationUtility::translate('importer.errors', $this->extensionName) .
                DebugUtility::formatArray($errors) . '</pre>',
            );
        }

        return true;
    }
}
