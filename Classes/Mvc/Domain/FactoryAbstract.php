<?php
namespace Innologi\Decosdata\Mvc\Domain;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
/**
 * Factory Abstract
 *
 * By default, you should overrule:
 * - $repository
 * - setProperties()
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class FactoryAbstract implements SingletonInterface {

	/**
	 * @var string
	 */
	protected $objectType;

	/**
	 * @var \Innologi\Decosdata\Mvc\Domain\RepositoryAbstract
	 */
	protected $repository;

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var integer
	 */
	protected $storagePid;

	/**
	 * @var array
	 */
	protected $objectCache = [];

	/**
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager)
	{
		$this->objectManager = $objectManager;
	}

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->objectType = preg_replace([
			'/\\\\Factory\\\\(?!.*\\\\Factory\\\\)/',
			'/Factory$/'
		], [
			'\\\\Model\\\\',
			''
		], get_class($this));
	}

	/**
	 * Creates and returns domain object from data.
	 *
	 * @param array $data field => value
	 * @return \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
	 */
	public function create(array $data) {
		/* @var $object \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject */
		$object = $this->objectManager->get($this->objectType);
		$this->setProperties($object, $data);
		$this->setDefaultProperties($object, $data);
		return $object;
	}

	/**
	 * Sets properties of domain object
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object
	 * @param array $data
	 * @return void
	 */
	protected function setProperties(\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object, array $data) {
		// overrule this method for setting object properties
	}

	/**
	 * Sets default properties in the creation process of domain object.
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object
	 * @param array $data field => value
	 * @return void
	 */
	protected function setDefaultProperties(\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object, array $data) {
		if (isset($data['pid'])) {
			$object->setPid((int) $data['pid']);
		}
		if (isset($data['uid'])) {
			$object->_setProperty('uid', (int) $data['uid']);
			// if uid is known, we assume this is an existing and unchanged record
			$object->_memorizeCleanState();
		}
	}

	/**
	 * Creates, stores and returns domain object with uid.
	 *
	 * Because it is stored and returned clean here already, it will speed up any persistence
	 * job handled by the persistenceManager (which is much slower) if the object remains
	 * unmodified (especially useful for ValueObjects). Relations can still be handled by
	 * the persistenceManager.
	 *
	 * @param array $data
	 * return \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
	 */
	public function createAndStoreObject(array $data) {
		// prevents a configurationManager check, which speeds up a process of thousands of inserts
		if ($this->storagePid !== NULL) {
			$data['pid'] = $this->storagePid;
		}
		// $data is provided with defaults 'pid' (if not previously set) and 'uid'
		$this->repository->insertRecord($data);
		/* @var $object \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject */
		$object = $this->create($data);
		return $object;
	}

	/**
	 * Sets Storage Pid
	 *
	 * @param integer $storagePid
	 * @return void
	 */
	public function setStoragePid($storagePid) {
		$this->storagePid = $storagePid;
	}

}