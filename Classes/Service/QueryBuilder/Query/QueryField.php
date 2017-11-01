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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\Select;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\Where;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\From;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy;
/**
 * Query Content Field object
 *
 * Contains Query sub-configuration for a single content field.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryField implements QueryInterface {

	/**
	 * @var QueryContent
	 */
	protected $parent;

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Part\Select
	 */
	protected $select;

	/**
	 * @var array
	 */
	protected $from = array();

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Part\Where
	 */
	protected $where;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy
	 */
	protected $orderBy;

	/**
	 * Class constructor
	 *
	 * @param string $id
	 * @param QueryContent $parent
	 * @return
	 */
	public function __construct($id, QueryContent $parent) {
		$this->id = $id;
		$this->parent = $parent;
		$this->select = GeneralUtility::makeInstance(Select::class);
		$this->where = GeneralUtility::makeInstance(Where::class);
		$this->orderBy = GeneralUtility::makeInstance(OrderBy::class);
		return $this;
	}

	/**
	 * Returns Select object
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\Select
	 */
	public function getSelect() {
		return $this->select;
	}

	/**
	 * Returns From array
	 *
	 * @return array
	 */
	public function getFromAll() {
		return $this->from;
	}

	/**
	 * Returns From object. If it does not exist yet, it is created.
	 *
	 * Optional property $tables needs to consist of $alias => $table pairs.
	 *
	 * @param string $id
	 * @param array $tables
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\From
	 */
	public function getFrom($id, array $tables = array()) {
		if (!isset($this->from[$id])) {
			$this->from[$id] = GeneralUtility::makeInstance(From::class, $tables);
		}
		return $this->from[$id];
	}

	// @TODO ______inherent flaw? we have to check WITHIN a content field for any FROM's, soooo we could end up with duplicates, crashing the query. Wouldn't an alias-register be worthwhile?
	/**
	 * Returns whether the given FROM alias exists in this field
	 *
	 * @param string $id
	 * @return boolean
	 */
	public function hasFrom($id) {
		return isset($this->from[$id]);
	}

	// @LOW _this naming scheme is not consistent: getFromAll but setFrom? Why not getFrom, setFrom and getOneFrom instead? or setFromAll?
	/**
	 * Sets From array
	 *
	 * @param array $from
	 * @return $this
	 */
	public function setFrom(array $from) {
		$this->from = $from;
		return $this;
	}

	/**
	 * Returns Where object
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\Where
	 */
	public function getWhere() {
		return $this->where;
	}

	/**
	 * Returns OrderBy object
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy
	 */
	public function getOrderBy() {
		return $this->orderBy;
	}

	/**
	 * Returns id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns parent
	 *
	 * @return QueryContent
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Overrides parent
	 *
	 * @param QueryContent $parent
	 * @return $this
	 */
	public function setParent(QueryContent $parent) {
		$this->parent = $parent;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::getParameters()
	 */
	public function getParameters() {
		return $this->parent->getParameters();
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::setParameters()
	 */
	public function setParameters(array $parameters) {
		$this->parent->setParameters($parameters);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::addParameter()
	 */
	public function addParameter($key, $value) {
		$this->parent->addParameter($key, $value);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::removeParameter()
	 */
	public function removeParameter($key) {
		$this->parent->removeParameter($key);
		return $this;
	}



	/**
	 * Ensures proper cloning of object properties
	 *
	 * @return void
	 */
	public function __clone() {
		$this->select = clone $this->select;
		$this->where = clone $this->where;
		$this->orderBy = clone $this->orderBy;
		foreach ($this->from as &$from) {
			$from = clone $from;
		}
	}
}
