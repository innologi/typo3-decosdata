<?php
namespace Innologi\Decosdata\Service\QueryBuilder\Query;
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Innologi\Decosdata\Service\Database\StatementFactory;
use Innologi\Decosdata\Service\QueryBuilder\QueryConfigurator;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
/**
 * Query object
 *
 * Represents an easily configurable Query configuration
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Query extends QueryIterator implements QueryInterface {

	/**
	 * @var ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager)
	{
		$this->objectManager = $objectManager;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::getParameters()
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::setParameters()
	 */
	public function setParameters(array $parameters) {
		$this->parameters = $parameters;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::addParameter()
	 */
	public function addParameter($key, $value) {
		// @TODO _validate parameters?
		$this->parameters[$key] = $value;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::removeParameter()
	 */
	public function removeParameter($key) {
		if (isset($this->parameters[$key])) {
			unset($this->parameters[$key]);
		}
		return $this;
	}

	/**
	 * Returns Query Content object. If it does not exist yet, it is created.
	 *
	 * @param string $id
	 * @return QueryContent
	 */
	public function getContent($id) {
		if (!isset($this->children[$id])) {
			$this->children[$id] = GeneralUtility::makeInstance(QueryContent::class, $id, $this);
		}
		return $this->children[$id];
	}

	/**
	 * Resets specific parts for all content/fields
	 *
	 * @param boolean $select
	 * @param boolean $from
	 * @param boolean $where
	 * @param boolean $groupBy
	 * @param boolean $orderBy
	 * @return $this
	 */
	public function resetParts($select, $from = FALSE, $where = FALSE, $groupBy = FALSE, $orderBy = FALSE) {
		/** @var $content \Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent */
		foreach ($this->children as $queryContent) {
			if ($orderBy) $queryContent->getOrderBy()->setTableAlias(NULL)->setField(NULL)->setPriority(NULL)->setSortOrder(NULL);
			if ($groupBy) $queryContent->getGroupBy()->setPriority(NULL);
			/** @var $queryField \Innologi\Decosdata\Service\QueryBuilder\Query\QueryField */
			foreach ($queryContent as $queryField) {
				if ($select) $queryField->getSelect()->setTableAlias(NULL)->setField(NULL)->setWrap([]);
				if ($where) $queryField->getWhere()->setConstraint(NULL);
				if ($orderBy) $queryField->getOrderBy()->setTableAlias(NULL)->setField(NULL)->setPriority(NULL)->setSortOrder(NULL);
				if ($from) $queryField->setFrom([]);
			}
		}
		return $this;
	}

	/**
	 * Build a Statement object from this Query object
	 *
	 * @return \Innologi\Decosdata\Service\Database\Statement
	 */
	public function createStatement() {
		/** @var $statementFactory \Innologi\Decosdata\Service\Database\StatementFactory */
		$statementFactory = $this->objectManager->get(StatementFactory::class);
		/** @var $queryConfigurator \Innologi\Decosdata\Service\QueryBuilder\QueryConfigurator */
		$queryConfigurator = $this->objectManager->get(QueryConfigurator::class);

		# @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
		$statementFactory->setLimit($this->limit, $this->offset);
		##################
		return $statementFactory->create(
			$queryConfigurator->transformConfiguration($this),
			$this->getParameters()
		);
	}

	# @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
		# keep in mind this is now also used in crumbpath
	protected $limit;
	protected $offset;
	public function setLimit($limit = NULL, $offset = NULL) {
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}

}
