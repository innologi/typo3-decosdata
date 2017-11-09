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
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;
use Innologi\Decosdata\Utility\DebugUtility;
/**
 * Importer Task
 *
 * Task-implementation of ImporterService
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImporterTask extends AbstractTask {
	# @TODO make the task configurable in which XML's to update, removing the auto_update check in the import table

	/**
	 * @var string
	 */
	protected $extensionName = 'Decosdata';

	/**
	 * @var array
	 */
	public $selectedImports = array();

	/**
	 * Execute task logic
	 *
	 * @return boolean
	 * @throws \Exception
	 */
	public function execute() {
		$bootstrap = new Bootstrap();
		$bootstrap->initialize(array(
			'pluginName' => 'Importer',
			'extensionName' => $this->extensionName,
			'vendorName' => 'Innologi'
		));

		/* @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

		/* @var $importerService \Innologi\Decosdata\Service\Importer\ImporterService */
		$importerService = $objectManager->get(\Innologi\Decosdata\Service\Importer\ImporterService::class);
		$importerService->importUidSelection($this->selectedImports);

		// persist any lingering data
		/* @var $persistenceManager \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager */
		$persistenceManager = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
		$persistenceManager->persistAll();

		$errors = $importerService->getErrors();
		if (!empty($errors)) {
			throw new \Exception(
				'<pre>' . LocalizationUtility::translate('importer.errors', $this->extensionName) .
				DebugUtility::formatArray($errors) . '</pre>'
			);
		}

		return TRUE;
	}

}
