<?php
namespace Innologi\Decosdata\Command;
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Innologi\Decosdata\Library\TraceLogger\SymfonyStyleLogger;
use Innologi\Decosdata\Utility\DebugUtility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
/**
 * Import Run Command
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImportRunCommand extends Command {

	/**
	 * @var string
	 */
	protected $extensionName = 'Decosdata';

	/**
	 * Configure the command by defining the name, options and arguments
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setDescription(
			'Process imports by uid'
		)->addArgument(
			'uid-list',
			InputArgument::REQUIRED | InputArgument::IS_ARRAY,
			'Required space-separated list of import UIDs to process.'
		)->addOption(
			'trace-log',
			't',
			InputOption::VALUE_NONE,
			'Enables basic trace log. Higher verbosity level logs more details.'
		);
		// @LOW setHelp()
		// @LOW addUsage()
		// @LOW disableSimulateArg?
	}

	/**
	 * Executes the command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		// Make sure the _cli_ user is loaded
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeBackendAuthentication();

		$output->setDecorated(TRUE);
		$io = new SymfonyStyle($input, $output);
		$io->title('Processing imports...');
		$uidArray = $input->getArgument('uid-list');
		$traceLogEnabled = (bool) $input->getOption('trace-log');

		try {
			$bootstrap = new \TYPO3\CMS\Extbase\Core\Bootstrap();
			$bootstrap->initialize(array(
				'pluginName' => 'Importer',
				'extensionName' => $this->extensionName,
				'vendorName' => 'Innologi'
			));

			/* @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
			$objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
			/* @var $importerService \Innologi\Decosdata\Service\Importer\ImporterService */
			$importerService = $objectManager->get(\Innologi\Decosdata\Service\Importer\ImporterService::class);
			if ($traceLogEnabled) {
				$importerService->setLogger($objectManager->get(SymfonyStyleLogger::class, $io));
			}
			$importerService->importUidSelection($uidArray);

			// persist any lingering data
			/* @var $persistenceManager \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager */
			$persistenceManager = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
			$persistenceManager->persistAll();

			$errors = $importerService->getErrors();
			if (!empty($errors)) {
				throw new \Exception(
					LocalizationUtility::translate('importer.errors', $this->extensionName) .
					DebugUtility::formatArray($errors)
				);
			}

			$io->success('Imports processed');
		} catch (\Exception $e) {
			$message = '';
			if ($e->getCode()) {
				$message .= '[' . $e->getCode() . '] ';
			}
			$io->error($message . $e->getMessage());
		}
	}

}