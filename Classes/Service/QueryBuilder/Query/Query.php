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
use Innologi\Decosdata\Service\QueryBuilder\StatementFactory;
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
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $parameters = array();

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
	 * Creates Query Content Object, adds it and returns it.
	 *
	 * @param string $id
	 * @return QueryContent
	 */
	public function createContent($id) {
		$object = GeneralUtility::makeInstance(QueryContent::class, $id, $this);
		$this->children[$id] = $object;
		return $object;
	}

	/**
	 * Build a Statement object from this Query object
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Statement
	 */
	public function createStatement() {
		/** @var $statementFactory \Innologi\Decosdata\Service\QueryBuilder\StatementFactory */
		$statementFactory = $this->objectManager->get(StatementFactory::class);
		# @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
		$statementFactory->setLimit($this->limit, $this->offset);
		##################
		return $statementFactory->createFromQuery($this);
	}

	# @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
	protected $limit;
	protected $offset;
	public function setLimit($limit = NULL, $offset = NULL) {
		$this->limit = $limit;
		$this->offset = $offset;
	}

}
