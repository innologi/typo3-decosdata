<?php
namespace Innologi\Decosdata\Service;
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
use TYPO3\CMS\Core\SingletonInterface;
use Innologi\Decosdata\Exception\PaginationError;
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
/**
 * Pagination Service
 *
 * Provides the pagination information for any configuration/Query combination.
 * Holds several key variables for any pagebrowser to be displayed afterwards.
 *
 * Works in tandem with the PageBrowserViewHelper.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PaginateService implements SingletonInterface {
	// @LOW _consider that an Option-like interface for pagination could improve maintainability even further:
	// a class which provides both the Query and the Display logic, completely self-contained, implementing
	// an interface tht is called upon by QueryBuilder and the Pagebrowser VH. Maybe worth doing if there
	// will be more pagebrowsers
	// @LOW _note all the MySQL keywords in yearly.. should be supplied by QueryProvider classes

	/**
	 * @var \Innologi\Decosdata\Service\ParameterService
	 * @inject
	 */
	protected $parameterService;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
	 * @inject
	 */
	protected $constraintFactory;

	/**
	 * @var array
	 */
	protected $supportedTypes = array('default', 'yearly');

	/**
	 * @var integer
	 */
	protected $currentPage = 1;

	/**
	 * @var integer
	 */
	protected $pageCount = 1;

	/**
	 * @var integer
	 */
	protected $resultCount;

	/**
	 * @var array
	 */
	protected $pageLabelMap = array();

	/**
	 * @var boolean
	 */
	protected $active = FALSE;

	/**
	 * Returns current page number
	 *
	 * @return integer
	 */
	public function getCurrentPage() {
		return $this->currentPage;
	}

	/**
	 * Returns page count
	 *
	 * @return integer
	 */
	public function getPageCount() {
		return $this->pageCount;
	}

	/**
	 * Returns total result count
	 *
	 * @return integer
	 */
	public function getResultCount() {
		return $this->resultCount;
	}

	/**
	 * Returns page mapping
	 *
	 * @return array
	 */
	public function getPageLabelMap() {
		return $this->pageLabelMap;
	}

	/**
	 * Confirms whether the service was successfully configured and active.
	 *
	 * Note that this also returns FALSE when there was only 1 page, so not ready !== error
	 *
	 * @return boolean
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * Paginate a Query object
	 *
	 * @param array $configuration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Query $query
	 * @return void
	 */
	public function configurePagination(array $configuration, Query $query) {
		$this->initializeConfiguration($configuration);

		switch ($configuration['type']) {
			case 'yearly':
				$this->configureYearly($configuration, $query);
				break;
			default:
				$this->configureDefault($configuration, $query);
		}
	}

	/**
	 * Initialize and validate shared configuration
	 *
	 * @param array $configuration
	 * @return void
	 * @throws \Innologi\Decosdata\Exception\PaginationError
	 */
	protected function initializeConfiguration(array $configuration) {
		// @LOW this can be more effective from within the switch
		if ( isset($configuration['type']) && !in_array($configuration['type'], $this->supportedTypes, TRUE) ) {
			throw new PaginationError(1449154955, array(
				'type', $configuration['type'], join('/', $this->supportedTypes)
			));
		}
		if ($configuration['pageLimit'] === NULL || $configuration['pageLimit'] <= 0) {
			throw new PaginationError(1449154970, array(
				'pageLimit', $configuration['pageLimit'], 100
			));
		}
		// sets currentPage to at least 1
		$this->currentPage = $this->parameterService->getParameterNormalized('page');

		// invalidate any previous configuration, just in case
		$this->active = FALSE;
	}

	/**
	 * Configures pagination by year
	 *
	 * @param array $configuration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Query $query
	 * @return void
	 * @throws \Innologi\Decosdata\Exception\PaginationError
	 */
	protected function configureYearly(array $configuration, Query $query) {
		// check for valid configuration values first
		$fieldId = $configuration['field'];
		if ($fieldId === NULL) {
			throw new PaginationError(1449154980, [
				'field', $fieldId, 5
			]);
		}

		// clone the Query and reset the parts not relevant for it
		$yQuery = clone $query;
		$yQuery->resetParts(TRUE, FALSE, FALSE, TRUE, TRUE);

		// set limit and ordering
		$yQuery->setLimit($configuration['pageLimit']);
		$queryContent = $yQuery->getContent('pbYear');
		$queryContent->getOrderBy()
			->setPriority(10)
			->setSortOrder('DESC');

		// define field values
		$tableAlias1 = 'itf_pbYear';
		$tableAlias2 = 'f_pbYear';
		$field = 'field_value';
		$queryField = $queryContent->getField('');

		// if an offset year was configured, apply it
		if ($configuration['offsetYear'] > 0) {
			$queryField->getWhere()->setConstraint(
				$this->constraintFactory->createConstraintByValue($field, $tableAlias1, '>=', $configuration['offsetYear'])->addWrapLocal('year', 'YEAR(|)')
			);
		}
		// if a maximum year was configured, apply it
		if ($configuration['maxYear'] > 0) {
			$queryField->getWhere()->addConstraint('pagebrowser-max',
				$this->constraintFactory->createConstraintByValue($field, $tableAlias1, '<=', $configuration['maxYear'])->addWrapLocal('year', 'YEAR(|)')
			);
		}

		// this pattern is used to prevent mysql warnings and errors at fields
		// that have no consistent date values
		$pattern = '^[0-9]{4}-(0[1-9]|1[0-2])-([0-2][0-9]|3[01])';
		// we're using the default wrap divider in the pattern, so it needs to change
		$wrapDivider = ':::|||:::';

		// add select
		$queryField->getSelect()
			->setTableAlias($tableAlias1)
			->setField($field)
			// the NULL will make pagination fail on purpose, without error
			->addWrap('if', 'IF(' . $wrapDivider . ' REGEXP (\'' . $pattern . '\'),YEAR(' . $wrapDivider . '),NULL)')
			->setWrapDivider($wrapDivider);

		// add from and keep it in a variable for later use
		$parameterKey = ':pagebrowserfield';
		$from = $queryField->getFrom(
			'pagebrowser-year', [
				$tableAlias1 => 'tx_decosdata_domain_model_itemfield',
				$tableAlias2 => 'tx_decosdata_domain_model_field'
			]
		)->setJoinType(
			'LEFT'
		)->setConstraint(
			$this->constraintFactory->createConstraintAnd([
				$this->constraintFactory->createConstraintByField('item', $tableAlias1, '=', 'uid', 'it'),
				$this->constraintFactory->createConstraintByField('field', $tableAlias1, '=', 'uid', $tableAlias2),
				$this->constraintFactory->createConstraintByValue('field_name', $tableAlias2, '=', $parameterKey)
			])
		);
		$yQuery->addParameter($parameterKey, $fieldId);

		// execute the cloned query to retrieve the years
		$statement = $yQuery->createStatement();
		$statement->execute();
		// prevent any years to be assigned to page 0
		$years = [0 => 0];
		while (($row = $statement->fetch()) !== FALSE) {
			$years[] = $row['pbYear'];
		}
		$statement->free();
		$pages = count($years);
		if ($pages > 1) {
			// removes the extra count of our manually added key 0
			$pages--;
		}

		// if current page nr exceeds page amount, replace it with last page nr
		if ($this->currentPage > $pages) {
			$this->currentPage = $pages;
		}

		// if NULL, it means that the date-field contains no valid date values
		if ($pages > 1 && $years[$this->currentPage] !== NULL) {
			// @LOW _do we want resultCount to be available or not?
			//$statement = $query->createStatement();
			//$statement->execute();
			//$this->resultCount = $statement->rowCount();
			//$statement->free();

			// add the FROM to the original Query object
			$queryField = $query->getContent('pagebrowser')->getField('year');
			$queryField->setFrom([$from]);
			$query->addParameter($parameterKey, $fieldId);

			// add the year restriction for the current page
			$parameterKey = ':pagebrowseryear';
			$queryField->getWhere()->setConstraint(
				$this->constraintFactory->createConstraintByValue($field, $tableAlias1, '=', $parameterKey)
					->addWrapLocal('if-begin', 'IF(' . $wrapDivider . ' REGEXP (\'' . $pattern . '\'),YEAR(' . $wrapDivider . ')')
					->addWrap('if-end', $wrapDivider . ',FALSE)')
					->setWrapDivider($wrapDivider)
			);
			$query->addParameter($parameterKey, $years[$this->currentPage]);

			// activate
			$this->pageCount = $pages;
			$this->pageLabelMap = $years;
			$this->active = TRUE;
		}
	}

	/**
	 * Configures default pagination
	 *
	 * @param array $configuration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Query $statement
	 * @return void
	 * @throws \Innologi\Decosdata\Exception\PaginationError
	 */
	protected function configureDefault(array $configuration, Query $query) {
		// @TODO ___use or clean up override
		// perPageLimit override through GET var
		/*$overrideVar = $this->controller->getPerPageLimitOverride();
		if ($overrideVar !== FALSE && isset($pBrowserConfArray['perPageLimitChoice'][$overrideVar])) {
		$pBrowserConfArray['perPageLimit'] = $pBrowserConfArray['perPageLimitChoice'][$overrideVar];
		}*/
		// check for valid configuration values first
		$perPageLimit = $configuration['perPageLimit'];
		if ($perPageLimit === NULL || $perPageLimit <= 0) {
			throw new PaginationError(1449154988, array(
				'perPageLimit', $perPageLimit, 20
			));
		}
		$pageLimit = $configuration['pageLimit'];

		// @FIX ___note that Query->createStatement() will happen more than once in a single request, and that each time will result
		// in the same workload. So we need to cache it on at least one level, e.g. locally. If useful, even query cache could
		// be applied here. Note that this would mean we can't add LIMIT .. OFFSET to $query without invalidating said cache!
		// So we have to be smart with caching the $queryParts that QueryConfigurator results into, and adding LIMIT there,
		// instead of making LIMIT a part of the cached results. For the time being, we could add LIMIT to $preparedStatement,
		// until we sort out what the definitive method is going to be. It might be smart to wait until all options have been
		// migrated to decosdata, so that we can see if any option needs to be able to alter LIMIT through $query.
		// ALSO: can LIMIT be provided as parameter? See PerPageLimitOverride above

		// @TODO ___execute() may return FALSE on error, we need to catch that
		// @TODO ___we might want a service that does Statement interaction for us, also in the repository.
		// Using our own service for the latter may reduce overhead caused by extbase (repository->query->statement() is an extbase construct anyway, not a FLOW backport)

		// calculate page amount, and only continue if > 1
		$statement = $query->createStatement();
		$statement->execute();
		$numRows = $statement->rowCount();
		$statement->free();

		// store count
		$this->resultCount = $numRows;

		$pages = (int) ceil($numRows / $perPageLimit);
		if ($pages > 1) {
			// if the page amount exceeds the allowed limit, set it to the limit
			if ($pages > $pageLimit) {
				$pages = $pageLimit;
			}

			// activate
			$this->pageCount = $pages;
			$query->setLimit($perPageLimit, $perPageLimit * ($this->currentPage - 1));
			$this->active = TRUE;
		}

		// if current page nr exceeds page amount, replace it with last page nr
		if ($this->currentPage > $pages) {
			$this->currentPage = $pages;
		}
	}

}
