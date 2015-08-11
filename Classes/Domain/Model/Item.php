<?php
namespace Innologi\Decospublisher7\Domain\Model;
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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
/**
 * Item domain model
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Item extends AbstractEntity {

	/**
	 * Decos Item Key
	 *
	 * @var string
	 */
	protected $itemKey;

	/**
	 * Item Type
	 *
	 * @var \Innologi\Decospublisher7\Domain\Model\ItemType
	 * @lazy
	 */
	protected $itemType;
	// @FIX ___see if this works in eclipse
	/**
	 * Item Field
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\Innologi\Decospublisher7\Domain\Model\ItemField[]
	 * @lazy
	 */
	protected $itemField;

	/**
	 * Item Blob
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\ItemBlob>
	 * @lazy
	 */
	protected $itemBlob;

	/**
	 * Import
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Import>
	 * @lazy
	 */
	protected $import;

	/**
	 * Parent Item
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Item>
	 * @lazy
	 */
	protected $parentItem;

	/**
	 * Child Item
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Item>
	 * @lazy
	 */
	protected $childItem;

	/**
	 * __construct
	 */
	public function __construct() {
		$this->initStorageObjects();
	}

	/**
	 * Initializes all ObjectStorage properties
	 *
	 * @return void
	 */
	protected function initStorageObjects() {
		// @LOW ___below tasks count for all domain model classes!
		// @LOW ___if we remove below task without change, we should add a use statement and alter these
		// @LOW ___you sure we want to use the new keyword? We could always utilize GeneralUtility
		$this->itemField = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->itemBlob = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->import = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->parentItem = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->childItem = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Returns the itemKey
	 *
	 * @return string $itemKey
	 */
	public function getItemKey() {
		return $this->itemKey;
	}

	/**
	 * Sets the itemKey
	 *
	 * @param string $itemKey
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function setItemKey($itemKey) {
		$this->itemKey = $itemKey;
		return $this;
	}

	/**
	 * Returns the itemType
	 *
	 * @return \Innologi\Decospublisher7\Domain\Model\ItemType $itemType
	 */
	public function getItemType() {
		return $this->itemType;
	}

	/**
	 * Sets the itemType
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\ItemType $itemType
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function setItemType(\Innologi\Decospublisher7\Domain\Model\ItemType $itemType) {
		$this->itemType = $itemType;
		return $this;
	}

	/**
	 * Adds an ItemField
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\ItemField $itemField
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function addItemField(\Innologi\Decospublisher7\Domain\Model\ItemField $itemField) {
		$this->itemField->attach($itemField);
		return $this;
	}

	/**
	 * Removes an ItemField
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\ItemField $itemField The ItemField to be removed
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function removeItemField(\Innologi\Decospublisher7\Domain\Model\ItemField $itemField) {
		$this->itemField->detach($itemField);
		return $this;
	}

	/**
	 * Returns the itemField
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\Innologi\Decospublisher7\Domain\Model\ItemField[] $itemField
	 */
	public function getItemField() {
		return $this->itemField;
	}

	/**
	 * Sets the itemField
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $itemField
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function setItemField(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $itemField) {
		$this->itemField = $itemField;
		return $this;
	}

	/**
	 * Adds an ItemBlob
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\ItemBlob $itemBlob
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function addItemBlob(\Innologi\Decospublisher7\Domain\Model\ItemBlob $itemBlob) {
		$this->itemBlob->attach($itemBlob);
		return $this;
	}

	/**
	 * Removes an ItemBlob
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\ItemBlob $itemBlob The ItemBlob to be removed
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function removeItemBlob(\Innologi\Decospublisher7\Domain\Model\ItemBlob $itemBlob) {
		$this->itemBlob->detach($itemBlob);
		return $this;
	}

	/**
	 * Returns the itemBlob
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\ItemBlob> $itemBlob
	 */
	public function getItemBlob() {
		return $this->itemBlob;
	}

	/**
	 * Sets the itemBlob
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\ItemBlob> $itemBlob
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function setItemBlob(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $itemBlob) {
		$this->itemBlob = $itemBlob;
		return $this;
	}

	/**
	 * Adds an Import
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Import $import
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function addImport(\Innologi\Decospublisher7\Domain\Model\Import $import) {
		$this->import->attach($import);
		return $this;
	}

	/**
	 * Removes an Import
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Import $import The Import to be removed
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function removeImport(\Innologi\Decospublisher7\Domain\Model\Import $import) {
		$this->import->detach($import);
		return $this;
	}

	/**
	 * Returns the import
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Import> $import
	 */
	public function getImport() {
		return $this->import;
	}

	/**
	 * Sets the import
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Import> $import
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function setImport(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $import) {
		$this->import = $import;
		return $this;
	}

	/**
	 * Adds a parentItem
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Item $parentItem
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function addParentItem(\Innologi\Decospublisher7\Domain\Model\Item $parentItem) {
		$this->parentItem->attach($parentItem);
		return $this;
	}

	/**
	 * Removes a parentItem
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Item $parentItem The Item to be removed
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function removeParentItem(\Innologi\Decospublisher7\Domain\Model\Item $parentItem) {
		$this->parentItem->detach($parentItem);
		return $this;
	}

	/**
	 * Returns the parentItem
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Item> $parentItem
	 */
	public function getParentItem() {
		return $this->parentItem;
	}

	/**
	 * Sets the parentItem
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Item> $parentItem
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function setParentItem(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $parentItem) {
		$this->parentItem = $parentItem;
		return $this;
	}

	/**
	 * Adds a childItem
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Item $childItem
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function addChildItem(\Innologi\Decospublisher7\Domain\Model\Item $childItem) {
		$this->childItem->attach($childItem);
		return $this;
	}

	/**
	 * Removes a childItem
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Item $childItem The Item to be removed
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function removeChildItem(\Innologi\Decospublisher7\Domain\Model\Item $childItem) {
		$this->childItem->detach($childItem);
		return $this;
	}

	/**
	 * Returns the childItem
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Item> $childItem
	 */
	public function getChildItem() {
		return $this->childItem;
	}

	/**
	 * Sets the childItem
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decospublisher7\Domain\Model\Item> $childItem
	 * @return \Innologi\Decospublisher7\Domain\Model\Item
	 */
	public function setChildItem(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $childItem) {
		$this->childItem = $childItem;
		return $this;
	}

}