<?php
namespace Innologi\Decosdata\Service\Importer;
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
use Innologi\Decosdata\Service\Importer\Exception\ValidationFailed;
use Innologi\Decosdata\Service\Importer\Exception\EmptyImportFile;
/**
 * Importer Service
 *
 * Imports Decos XML Imports.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImporterService implements SingletonInterface{

	/**
	 * @var \Innologi\Decosdata\Domain\Repository\ImportRepository
	 * @inject
	 */
	protected $importRepository;

	/**
	 * @var \Innologi\Decosdata\Service\Importer\Parser\ParserInterface
	 * @inject
	 */
	protected $parser;

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Will process all available imports, regardless of page uid
	 *
	 * @return void
	 */
	public function importAll() {
		$importCollection = $this->importRepository->findAllEverywhere();
		$this->importSelection($importCollection);
	}

	/**
	 * Will process a selection of imports given as parameter as uid
	 *
	 * @param array $uidArray
	 * @return void
	 */
	public function importUidSelection(array $uidArray) {
		$importCollection = $this->importRepository->findInUidEverywhere($uidArray);
		$this->importSelection($importCollection);
	}

	/**
	 * Will process a selection of imports given as parameter
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array $importCollection
	 * @return void
	 */
	public function importSelection($importCollection) {
		/* @var $import \Innologi\Decosdata\Domain\Model\Import */
		foreach ($importCollection as $import) {
			try {
				$this->importSingle($import);
			} catch (ValidationFailed $e) {
				// register the error and move on
				$this->errors[$import->getUid() . ':' . $import->getTitle()] = $e->getMessage();
			}
			// any other exception is so serious that we have to halt the entire process anyway
		}
	}

	/**
	 * Will process a single import
	 *
	 * @param \Innologi\Decosdata\Domain\Model\Import $import
	 * @return void
	 */
	public function importSingle(\Innologi\Decosdata\Domain\Model\Import $import) {
		$filePath = PATH_site . $import->getFile()->getOriginalResource()->getPublicUrl();
		if ( ($newHash = $this->getHashIfReadyForProcessing($filePath, $import->getHash())) === FALSE ) {
			// @LOW consider throwing an exception, which when caught will register the import as notUpdated?
			// either no file present, or the file has seen no change: no sense in continuing
			return;
		}

		$this->parser->processImport($import);
		// not using the File record's hash, because that one is set initially and could be updated by outside sources
		$import->setHash($newHash);
		// mark as processed
		$this->importRepository->update($import);
	}

	/**
	 * Returns hash of $filePath, only if it does not match $knownHash.
	 * Returns boolean FALSE if file does not exist or hash matches $knownHash.
	 *
	 * @param string $filePath
	 * @param string $knownHash
	 * @return string|boolean
	 */
	protected function getHashIfReadyForProcessing($filePath, $knownHash) {
		return file_exists($filePath) && ($newHash = md5_file($filePath)) !== $knownHash ? $newHash : FALSE;

	}

	/**
	 * Returns any errors registered by the service
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

}
