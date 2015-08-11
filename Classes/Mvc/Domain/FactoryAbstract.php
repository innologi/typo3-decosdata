<?php
namespace Innologi\Decospublisher7\Mvc\Domain;
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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
/**
 * Factory Abstract
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class FactoryAbstract implements FactoryInterface,SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \Innologi\Decospublisher7\Mvc\Domain\RepositoryAbstract
	 */
	protected $repository;

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var array
	*/
	protected $objectCache = array();

	/**
	 * Class constructor
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		// because we want objectManager in __construct, we can't rely on DI as it is always later
		$this->objectManager = $objectManager;
		$this->initializeDefaultFieldValues();
	}

	/**
	 * Sets default field values for domain object to be merged with $data parameters
	 * of other factory methods.
	 *
	 * @return void
	 */
	protected function initializeDefaultFieldValues() {
		if (isset($this->fields['pid'])) {
			/* @var $configurationManager \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface */
			$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
			$typoscript = $configurationManager->getConfiguration(
				ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
			);
			$this->fields['pid'] = $typoscript['persistence']['storagePid'];
			// @TODO ___test this with both plugin and module versions of storagePid
			// @LOW ___throw exception on missing storagePid?
		}
		if (isset($this->fields['crdate'])) {
			$this->fields['crdate'] = $GLOBALS['EXEC_TIME'];
		}
		if (isset($this->fields['tstamp'])) {
			$this->fields['tstamp'] = $GLOBALS['EXEC_TIME'];
		}
	}

	/**
	 * Creates, stores and returns domain object with uid and merged
	 * default field values
	 *
	 * @param array $data
	 * return \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
	 */
	public function createAndStoreObject(array $data) {
		// merges data into fields
		$data = array_merge($this->fields, $data);
		/* @var $object \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject */
		$object = $this->createObject($data);
		$object->_setProperty(
			'uid',
			$this->repository->insertRecord($data)
		);
		return $object;
	}

}