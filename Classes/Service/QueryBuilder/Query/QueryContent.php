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
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\GroupBy;
/**
 * Query Content object
 *
 * Contains Query sub-configuration for one content and all its fields.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryContent extends QueryIterator implements QueryInterface {

	/**
	 * @var QueryInterface
	 */
	protected $parent;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy
	 */
	protected $orderBy;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Part\GroupBy
	 */
	protected $groupBy;

	/**
	 * Class constructor
	 *
	 * @param string $id
	 * @param QueryInterface $parent
	 * @return $this
	 */
	public function __construct($id, QueryInterface $parent) {
		$this->id = $id;
		$this->parent = $parent;
		$this->orderBy = GeneralUtility::makeInstance(OrderBy::class);
		$this->groupBy = GeneralUtility::makeInstance(GroupBy::class);
		return $this;
	}

	/**
	 * Returns title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets title
	 *
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title) {
		$this->title = $title;
		return $this;
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
	 * Returns GroupBy object
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\GroupBy
	 */
	public function getGroupBy() {
		return $this->groupBy;
	}

	/**
	 * Returns Query Field object. If it does not exist yet, it is created.
	 *
	 * @param string $subId
	 * @return QueryField
	 */
	public function getField($subId) {
		$id = $this->id . $subId;
		if (!isset($this->children[$id])) {
			$this->children[$id] = GeneralUtility::makeInstance(QueryField::class, $id, $this);
		}
		return $this->children[$id];
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
