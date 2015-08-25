<?php
namespace Innologi\Decospublisher7\Service\Importer;
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
use Innologi\Decospublisher7\Service\Importer\Exception\ValidationFailed;
use Innologi\Decospublisher7\Service\Importer\Exception\EmptyImportFile;
/**
 * Importer Service
 *
 * Imports Decos XML Imports.
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImporterService implements SingletonInterface{

	/**
	 * @var \Innologi\Decospublisher7\Domain\Repository\ImportRepository
	 * @inject
	 */
	protected $importRepository;

	/**
	 * @var \Innologi\Decospublisher7\Service\Importer\Parser\ParserInterface
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
	 * Will process a selection of imports given as parameter
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array $importCollection
	 * @return void
	 */
	public function importSelection($importCollection) {
		/* @var $import \Innologi\Decospublisher7\Domain\Model\Import */
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
	 * @param \Innologi\Decospublisher7\Domain\Model\Import $import
	 * @return void
	 */
	public function importSingle(\Innologi\Decospublisher7\Domain\Model\Import $import) {
		// @TODO ___check if import has changed
		$this->parser->processImport($import);
		// @TODO ___and what about marking the import with hash and tstamp fields!
		#$this->importRepository->update($import);
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
