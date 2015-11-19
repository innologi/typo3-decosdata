<?php
namespace Innologi\Decosdata\Service\QueryBuilder\Query\Part;
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
 * Select Query Part Object
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Select {

	/**
	 * @var string
	 */
	protected $tableAlias;

	/**
	 * @var string
	 */
	protected $field;

	/**
	 * @var array
	 */
	protected $wrap = array();

	/**
	 * Returns Table Alias
	 *
	 * @return string
	 */
	public function getTableAlias() {
		return $this->tableAlias;
	}

	/**
	 * Sets Table Alias
	 *
	 * @param string $tableAlias
	 * @return $this
	 */
	public function setTableAlias($tableAlias) {
		$this->tableAlias = $tableAlias;
		return $this;
	}

	/**
	 * Returns field
	 *
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * Sets field
	 *
	 * @param string $field
	 * @return $this
	 */
	public function setField($field) {
		$this->field = $field;
		return $this;
	}

	/**
	 * Returns wrap
	 *
	 * @return array
	 */
	public function getWrap() {
		return $this->wrap;
	}

	/**
	 * Sets wrap
	 *
	 * @param array $wrap
	 * @return $this
	 */
	public function setWrap(array $wrap) {
		$this->wrap = $wrap;
		return $this;
	}

	/**
	 * Add wrap by key
	 *
	 * @param string $key
	 * @param string $wrap
	 * @return $this
	 */
	public function addWrap($key, $wrap) {
		$this->wrap[$key] = $wrap;
		return $this;
	}

	/**
	 * Remove wrap by key
	 *
	 * @param string $key
	 * @return $this
	 */
	public function removeWrap($key) {
		if (isset($this->wrap[$key])) {
			unset($this->wrap[$key]);
		}
		return $this;
	}

}
