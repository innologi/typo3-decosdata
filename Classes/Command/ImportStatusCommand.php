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
use TYPO3\CMS\Core\Core\Bootstrap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
/**
 * Import Status Command
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImportStatusCommand extends Command {

	/**
	 * @var array
	 */
	protected $answers = [
		0 => 'no',
		1 => 'yes'
	];

	/**
	 * Configure the command by defining the name, options and arguments
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setDescription(
			'Show status of imports by uid'
		)->addArgument(
			'uid-list',
			InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
			'Optional space-separated list of import UIDs to check.'
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
		Bootstrap::getInstance()->initializeBackendAuthentication();

		$output->setDecorated(TRUE);
		$io = new SymfonyStyle($input, $output);
		$io->title($this->getDescription());
		$uidArray = $input->getArgument('uid-list');

		try {
			/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
			$objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
			/** @var $importRepository \Innologi\Decosdata\Domain\Repository\ImportRepository */
			$importRepository = $objectManager->get(\Innologi\Decosdata\Domain\Repository\ImportRepository::class);
			$imports = empty($uidArray) ? $importRepository->findAllEverywhere() : $importRepository->findInUidEverywhere($uidArray);

			/** @var $import \Innologi\Decosdata\Domain\Model\Import */
			foreach ($imports as $import) {
				$prefix = '[' . $import->getUid() . ':' . $import->getTitle() . '] - ';
				$filePath = PATH_site . $import->getFile()->getOriginalResource()->getPublicUrl();

				$fileExists = file_exists($filePath);
				$knownHash = $import->getHash();
				$newHash = md5_file($filePath);
				$canBeUpdated = $fileExists && $knownHash !== $newHash;

				$io->writeln($prefix . 'file-path: ' . $filePath);
				$io->writeln($prefix . 'file-exists: ' . $this->answers[(int)$fileExists]);
				$io->writeln($prefix . 'known-hash: ' . $knownHash);
				$io->writeln($prefix . 'current-hash: ' . $newHash);
				$io->writeln($prefix . 'updatable: ' . $this->answers[(int)$canBeUpdated]);
				$io->newLine();
			}
		} catch (\Exception $e) {
			$io->error('[' . $e->getCode() . '] ' . $e->getMessage());
		}
	}

}