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
	 * @var QueryInterface
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
	 * Class constructor
	 *
	 * @param string $id
	 * @param QueryInterface $parent
	 * @return
	 */
	public function __construct($id, QueryInterface $parent) {
		$this->id = $id;
		$this->parent = $parent;
		$this->select = GeneralUtility::makeInstance(Select::class);
		$this->where = GeneralUtility::makeInstance(Where::class);
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
	 * @param string $id
	 * @param string $table
	 * @param string $alias
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\From
	 */
	public function getFrom($id, $table, $alias) {
		if (!isset($this->from[$id])) {
			$this->from[$id] = GeneralUtility::makeInstance(From::class, $table, $alias);
		}
		return $this->from[$id];
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
	 * Returns id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
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

}
