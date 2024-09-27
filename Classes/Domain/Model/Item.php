<?php

namespace Innologi\Decosdata\Domain\Model;

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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Item domain model
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Item extends AbstractEntity
{
    /**
     * Decos Item Key
     *
     * @var string
     */
    protected $itemKey;

    /**
     * Item Type
     *
     * @var \Innologi\Decosdata\Domain\Model\ItemType
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $itemType;

    /**
     * Item Field
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decosdata\Domain\Model\ItemField>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $itemField;

    /**
     * Item Blob
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decosdata\Domain\Model\ItemBlob>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $itemBlob;

    /**
     * Import
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decosdata\Domain\Model\Import>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $import;

    /**
     * Parent Item
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decosdata\Domain\Model\Item>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $parentItem;

    /**
     * Child Item
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Innologi\Decosdata\Domain\Model\Item>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected $childItem;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     */
    protected function initStorageObjects()
    {
        // as long as ObjectStorage is hardcoded throughout extbase, we can use the new keyword
        $this->itemField = new ObjectStorage();
        $this->itemBlob = new ObjectStorage();
        $this->import = new ObjectStorage();
        $this->parentItem = new ObjectStorage();
        $this->childItem = new ObjectStorage();
    }

    /**
     * Returns the itemKey
     *
     * @return string
     */
    public function getItemKey()
    {
        return $this->itemKey;
    }

    /**
     * Sets the itemKey
     *
     * @param string $itemKey
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function setItemKey($itemKey)
    {
        $this->itemKey = $itemKey;
        return $this;
    }

    /**
     * Returns the itemType
     *
     * @return \Innologi\Decosdata\Domain\Model\ItemType
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * Sets the itemType
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function setItemType(\Innologi\Decosdata\Domain\Model\ItemType $itemType)
    {
        $this->itemType = $itemType;
        return $this;
    }

    /**
     * Adds an ItemField
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function addItemField(\Innologi\Decosdata\Domain\Model\ItemField $itemField)
    {
        $this->itemField->attach($itemField);
        return $this;
    }

    /**
     * Removes an ItemField
     *
     * @param \Innologi\Decosdata\Domain\Model\ItemField $itemField The ItemField to be removed
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function removeItemField(\Innologi\Decosdata\Domain\Model\ItemField $itemField)
    {
        $this->itemField->detach($itemField);
        return $this;
    }

    /**
     * Returns the itemField
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\Innologi\Decosdata\Domain\Model\ItemField[] $itemField
     */
    public function getItemField()
    {
        return $this->itemField;
    }

    /**
     * Sets the itemField
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function setItemField(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $itemField)
    {
        $this->itemField = $itemField;
        return $this;
    }

    /**
     * Adds an ItemBlob
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function addItemBlob(\Innologi\Decosdata\Domain\Model\ItemBlob $itemBlob)
    {
        $this->itemBlob->attach($itemBlob);
        return $this;
    }

    /**
     * Removes an ItemBlob
     *
     * @param \Innologi\Decosdata\Domain\Model\ItemBlob $itemBlob The ItemBlob to be removed
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function removeItemBlob(\Innologi\Decosdata\Domain\Model\ItemBlob $itemBlob)
    {
        $this->itemBlob->detach($itemBlob);
        return $this;
    }

    /**
     * Returns the itemBlob
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\Innologi\Decosdata\Domain\Model\ItemBlob[] $itemBlob
     */
    public function getItemBlob()
    {
        return $this->itemBlob;
    }

    /**
     * Sets the itemBlob
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function setItemBlob(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $itemBlob)
    {
        $this->itemBlob = $itemBlob;
        return $this;
    }

    /**
     * Adds an Import
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function addImport(\Innologi\Decosdata\Domain\Model\Import $import)
    {
        $this->import->attach($import);
        return $this;
    }

    /**
     * Removes an Import
     *
     * @param \Innologi\Decosdata\Domain\Model\Import $import The Import to be removed
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function removeImport(\Innologi\Decosdata\Domain\Model\Import $import)
    {
        $this->import->detach($import);
        return $this;
    }

    /**
     * Returns the import
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\Innologi\Decosdata\Domain\Model\Import[] $import
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * Sets the import
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function setImport(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $import)
    {
        $this->import = $import;
        return $this;
    }

    /**
     * Adds a parentItem
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function addParentItem(self $parentItem)
    {
        $this->parentItem->attach($parentItem);
        return $this;
    }

    /**
     * Removes a parentItem
     *
     * @param \Innologi\Decosdata\Domain\Model\Item $parentItem The Item to be removed
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function removeParentItem(self $parentItem)
    {
        $this->parentItem->detach($parentItem);
        return $this;
    }

    /**
     * Returns the parentItem
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\Innologi\Decosdata\Domain\Model\Item[] $parentItem
     */
    public function getParentItem()
    {
        return $this->parentItem;
    }

    /**
     * Sets the parentItem
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function setParentItem(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $parentItem)
    {
        $this->parentItem = $parentItem;
        return $this;
    }

    /**
     * Adds a childItem
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function addChildItem(self $childItem)
    {
        $this->childItem->attach($childItem);
        return $this;
    }

    /**
     * Removes a childItem
     *
     * @param \Innologi\Decosdata\Domain\Model\Item $childItem The Item to be removed
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function removeChildItem(self $childItem)
    {
        $this->childItem->detach($childItem);
        return $this;
    }

    /**
     * Returns the childItem
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage|\Innologi\Decosdata\Domain\Model\Item[] $childItem
     */
    public function getChildItem()
    {
        return $this->childItem;
    }

    /**
     * Sets the childItem
     *
     * @return \Innologi\Decosdata\Domain\Model\Item
     */
    public function setChildItem(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $childItem)
    {
        $this->childItem = $childItem;
        return $this;
    }
}
