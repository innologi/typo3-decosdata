<?php
namespace Innologi\Decospublisher7\Domain\Factory;
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
use Innologi\Decospublisher7\Mvc\Domain\FactoryAbstract;
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * ItemType factory
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemTypeFactory extends FactoryAbstract {

	/**
	 * @var array
	 */
	protected $fields = array(
		'pid' => 0,
		'item_type' => '',
		'crdate' => 0,
		'tstamp' => 0
	);

	/**
	 * @var \Innologi\Decospublisher7\Domain\Repository\ItemTypeRepository
	 * @inject
	 */
	protected $repository;

	/**
	 * Creates and returns unpersisted domain object from data
	 *
	 * @param array $data field => value
	 * @return \Innologi\Decospublisher7\Domain\Model\ItemType
	 */
	public function create(array $data) {
		/* @var $object \Innologi\Decospublisher7\Domain\Model\ItemType */
		$object = GeneralUtility::makeInstance('Innologi\\Decospublisher7\\Domain\\Model\\ItemType');
		$object->setPid($data['pid']);
		$object->setItemType($data['item_type']);
		return $object;
	}

	/**
	 * Retrieve ItemType Object from, in this order until successful:
	 * - local object cache
	 * - repository
	 * - created by parameters
	 *
	 * @param string $type
	 * @return \Innologi\Decospublisher7\Domain\Model\ItemType
	 */
	public function retrieveItemTypeObjectByItemTypeString($type) {
		if (!isset($objectCache[$type])) {
			/* @var $typeObject \Innologi\Decospublisher7\Domain\Model\ItemType */
			$typeObject = $this->repository->findOneByItemType($type);
			if ($typeObject === NULL) {
				$typeObject = $this->createAndStoreObject(
					array('item_type' => $type)
				);
			}
			$objectCache[$type] = $typeObject;
		}
		return $objectCache[$type];
	}

}