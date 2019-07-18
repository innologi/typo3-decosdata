<?php
namespace Innologi\Decosdata\Domain\Factory;
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
use Innologi\Decosdata\Mvc\Domain\FactoryAbstract;
use Innologi\Decosdata\Exception\MissingObjectProperty;
use Innologi\Decosdata\Domain\Repository\ItemRepository;
/**
 * Item factory
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemFactory extends FactoryAbstract {

	/**
	 * @var ItemRepository
	 */
	protected $repository;

	/**
	 *
	 * @param ItemRepository $repository
	 * @return void
	 */
	public function injectRepository(ItemRepository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * Sets properties of domain object
	 *
	 * @param \Innologi\Decosdata\Domain\Model\Item $object
	 * @param array $data
	 * @return void
	 * @throws \Innologi\Decosdata\Exception\MissingObjectProperty
	 */
	protected function setProperties(\Innologi\Decosdata\Domain\Model\Item $object, array $data) {
		if (!isset($data['item_key'][0])) {
			throw new MissingObjectProperty(1448549909, [
				'item_key',
				'Item'
			]);
		}
		$object->setItemKey($data['item_key']);

		if (isset($data['item_type'])) {
			$object->setItemType($data['item_type']);
		}
		if (isset($data['import']) && is_array($data['import'])) {
			foreach ($data['import'] as $import) {
				$object->addImport($import);
			}
		}
		if (isset($data['item_field']) && is_array($data['item_field'])) {
			foreach ($data['item_field'] as $itemField) {
				$object->addItemField($itemField);
			}
		}
		if (isset($data['item_blob']) && is_array($data['item_blob'])) {
			foreach ($data['item_blob'] as $itemBlob) {
				$object->addItemBlob($itemBlob);
			}
		}
		if (isset($data['child_item']) && is_array($data['child_item'])) {
			foreach ($data['child_item'] as $childItem) {
				$object->addChildItem($childItem);
			}
		}
		if (isset($data['parent_item']) && is_array($data['parent_item'])) {
			foreach ($data['parent_item'] as $parentItem) {
				$object->addParentItem($parentItem);
			}
		}
	}

	/**
	 * Retrieve Item Object from, in this order until successful:
	 * - repository, values replaced by optional data parameters
	 * - newly created by optional data parameters
	 *
	 * @param string $itemKey
	 * @param array $data
	 * @return \Innologi\Decosdata\Domain\Model\Item
	 */
	public function getByItemKey($itemKey, array $data = []) {
		/* @var $item \Innologi\Decosdata\Domain\Model\Item */
		$item = $this->repository->findOneByItemKey($itemKey);
		if ($item === NULL) {
			if (empty($data)) {
				// set required parameters
				$data['item_key'] = $itemKey;
			}
			$item = $this->create($data);
		} elseif (!empty($data)) {
			// would be useless if no additional $data was given, as itemKey is already set
			$this->setProperties($item, $data);
		}
		return $item;
	}

}