<?php
namespace Innologi\Decosdata\Service\QueryBuilder\Query;
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
 * Query object iterator abstract
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class QueryIterator implements \Iterator {

	/**
	 * @var array
	 */
	protected $children = array();



	/**
	 * Ensures proper cloning of object properties
	 *
	 * @return void
	 */
	public function __clone() {
		foreach ($this->children as &$child) {
			$child = clone $child;
			$child->setParent($this);
		}
	}

	/**************************
	 * Iterator implementation
	 **************************/

	public function current () {
		return current($this->children);
	}

	public function next () {
		return next($this->children);
	}

	public function key () {
		return key($this->children);
	}

	public function valid () {
		return current($this->children) !== FALSE;
	}

	public function rewind () {
		reset($this->children);
	}

}
