<?php
namespace Innologi\Decosdata\Domain\Model;
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
use \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
/**
 * ItemType domain model
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemType extends AbstractValueObject {

	/**
	 * Type
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $itemType;

	/**
	 * Returns the type
	 *
	 * @return string $itemType
	 */
	public function getItemType() {
		return $this->itemType;
	}

	/**
	 * Sets the type
	 *
	 * @param string $itemType
	 * @return \Innologi\Decosdata\Domain\Model\ItemType
	 */
	public function setItemType($itemType) {
		$this->itemType = $itemType;
		return $this;
	}

}