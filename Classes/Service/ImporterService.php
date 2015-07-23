<?php
namespace Innologi\Decospublisher7\Service;
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

/**
 * Importer Service
 *
 * Imports Decos XML Imports.
 *
 * @package decospublisher7
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class ImporterService {

	# @TODO doc

	/**
	 * @var \Innologi\Decospublisher7\Domain\Repository\ImportRepository
	 * @inject
	 */
	protected $importRepository;


	public function importAll() {
		$importCollection = $this->importRepository->findAll();
		$this->importSelection($importCollection);
	}

	public function importSelection($importCollection) {
		foreach ($importCollection as $import) {
			$this->processImport($import);
		}
	}

	public function importSingle($import) {
		$this->processImport($import);
	}

	protected function processImport($import) {

	}
}
