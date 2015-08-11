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
 * ItemField domain model
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemField extends AbstractEntity {

	/**
	 * Field Value
	 *
	 * @var string
	 */
	protected $fieldValue;

	/**
	 * Field
	 *
	 * @var \Innologi\Decospublisher7\Domain\Model\Field
	 */
	protected $field;

	/**
	 * Item
	 *
	 * @var \Innologi\Decospublisher7\Domain\Model\Item
	 */
	protected $item;

	/**
	 * Returns the value
	 *
	 * @return string $fieldValue
	 */
	public function getFieldValue() {
		return $this->fieldValue;
	}

	/**
	 * Sets the value
	 *
	 * @param string $fieldValue
	 * @return \Innologi\Decospublisher7\Domain\Model\ItemField
	 */
	public function setFieldValue($fieldValue) {
		$this->fieldValue = $fieldValue;
		return $this;
	}

	/**
	 * Returns the field
	 *
	 * @return \Innologi\Decospublisher7\Domain\Model\Field $field
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * Sets the field
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Field $field
	 * @return \Innologi\Decospublisher7\Domain\Model\ItemField
	 */
	public function setField(\Innologi\Decospublisher7\Domain\Model\Field $field) {
		$this->field = $field;
		return $this;
	}

	/**
	 * Returns the item
	 *
	 * @return \Innologi\Decospublisher7\Domain\Model\Item $item
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 * Sets the item
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Item $item
	 * @return \Innologi\Decospublisher7\Domain\Model\ItemField
	 */
	public function setItem(\Innologi\Decospublisher7\Domain\Model\Item $item) {
		$this->item = $item;
		return $this;
	}

}