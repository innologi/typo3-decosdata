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
 * From Query Part Object
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class From extends ConstraintContainer {

	/**
	 * @var array
	 */
	protected $tables;

	/**
	 * @var string
	 */
	protected $joinType;

	/**
	 * Class constructor
	 *
	 * Optional parameter needs to consist of $alias => $table pairs.
	 *
	 * @param array $tables
	 * @return $this
	 */
	public function __construct(array $tables = []) {
		return $this->setTables($tables);
	}

	/**
	 * Returns Table by alias
	 *
	 * @return string
	 */
	public function getTableNameByAlias($alias) {
		return isset($this->tables[$alias]) ? $this->tables[$alias] : NULL;
	}

	/**
	 * Sets table
	 *
	 * @param string $table
	 * @param string $alias
	 * @return $this
	 */
	public function addTable($table, $alias) {
		$this->tables[$alias] = $table;
		return $this;
	}

	/**
	 * Returns all $alias => $table pairs
	 *
	 * @return array
	 */
	public function getTables() {
		return $this->tables;
	}

	/**
	 * Sets or replaces entire table collection
	 *
	 * @param array $tables
	 * @return $this
	 */
	public function setTables(array $tables) {
		$this->tables = $tables;
		return $this;
	}

	/**
	 * Returns join type
	 *
	 * @return string
	 */
	public function getJoinType() {
		return $this->joinType;
	}

	/**
	 * Sets join type
	 *
	 * @param string $joinType
	 * @return $this
	 */
	public function setJoinType($joinType) {
		$this->joinType = $joinType;
		return $this;
	}

}
