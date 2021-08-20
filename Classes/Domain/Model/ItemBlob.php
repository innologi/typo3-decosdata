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
/**
 * ItemBlob domain model
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemBlob extends AbstractEntity {

	/**
	 * Decos Item Key
	 *
	 * @var string
	 */
	protected $itemKey;

	/**
	 * Blob sequence number
	 *
	 * @var integer
	 */
	protected $sequence;

	/**
	 * File reference
	 *
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
	 */
	protected $file;

	/**
	 * Item
	 *
	 * @var \Innologi\Decosdata\Domain\Model\Item
	 */
	protected $item;

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
	 * @return \Innologi\Decosdata\Domain\Model\ItemBlob
	 */
	public function setItemKey($itemKey) {
		$this->itemKey = $itemKey;
		return $this;
	}

	/**
	 * Returns the sequence
	 *
	 * @return string $sequence
	 */
	public function getSequence() {
		return $this->sequence;
	}

	/**
	 * Sets the sequence
	 *
	 * @param string $sequence
	 * @return \Innologi\Decosdata\Domain\Model\ItemBlob
	 */
	public function setSequence($sequence) {
		$this->sequence = $sequence;
		return $this;
	}

	/**
	 * Returns the file
	 *
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $file
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Sets the file
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $file
	 * @return \Innologi\Decosdata\Domain\Model\ItemBlob
	 */
	public function setFile(\TYPO3\CMS\Extbase\Domain\Model\FileReference $file) {
		$this->file = $file;
		return $this;
	}

	/**
	 * Returns the item
	 *
	 * @return \Innologi\Decosdata\Domain\Model\Item $item
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 * Sets the item
	 *
	 * @param \Innologi\Decosdata\Domain\Model\Item $item
	 * @return \Innologi\Decosdata\Domain\Model\ItemBlob
	 */
	public function setItem(\Innologi\Decosdata\Domain\Model\Item $item) {
		$this->item = $item;
		return $this;
	}

}