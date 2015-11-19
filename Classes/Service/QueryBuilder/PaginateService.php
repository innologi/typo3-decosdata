<?php
namespace Innologi\Decosdata\Service\QueryBuilder;
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
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
/**
 * Pagination Service
 *
 * Provides the pagination of queries, and holds several key variables
 * for any pagebrowser to be displayed afterwards.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PaginateService implements SingletonInterface {

	/**
	 * @var array
	 */
	protected $supportedTypes = array('default', 'yearly');

	// @TODO ___doc
	public function paginateQuery(array $configuration, Query $query) {
		if ( isset($configuration['type']) && !in_array($configuration['type'], $this->supportedTypes, TRUE) ) {
			// @TODO throw exception
		}
		switch ($configuration['type']) {
			#case 'yearly':
				#$this->paginateQueryYearly($configuration, $query);
				#break;
			default:
				$this->paginateQueryDefault($configuration, $query);
		}
	}

	// @TODO _finish this one
	protected function paginateQueryYearly(array $configuration, Query $query) {
		/*
		 * regex used to prevent mysql warnings and errors at fields that have
		 * no consistent date values
		 */
		$regex = '^[0-9]{4}-(0[1-9]|1[0-2])-([0-2][0-9]|3[01])';
		// secure parameters for query expansion
		$dateField = $GLOBALS['TYPO3_DB']->fullQuoteStr($configuration['dateField'], NULL);
		$offsetYear = $configuration['offsetYear'];
		$maxYear = $configuration['maxYear'];

		// alter a copy of the query to retrieve the existing years
		$yearQuery = $query;
		$yearQuery['SELECT'] = 'DISTINCT IF(itf_pbYear.fieldvalue REGEXP (\'' . $regex . '\')' .
			',YEAR(itf_pbYear.fieldvalue),\'....\') AS pbYear';
		$yearQuery['FROM'] .= '
					LEFT JOIN (tx_decospublisher_itemfield itf_pbYear)
						ON (it.uid=itf_pbYear.item_id AND itf_pbYear.fieldname=' . $dateField . ')';
		$yearQuery['ORDERBY'] = 'pbYear DESC';
		// expand the newest query with the supplied conditions
		$yearQuery['WHERE'] .= $offsetYear > 0 ? '
					AND YEAR(itf_pbYear.fieldvalue)>=' . $offsetYear : '';
		$yearQuery['WHERE'] .= $maxYear > 0 ? '
					AND YEAR(itf_pbYear.fieldvalue)<=' . $maxYear : '';
		$yearQuery['GROUPBY'] = '';
		$yearQuery['LIMIT'] .= $pageLimit > 0 ? $pageLimit : '';
		// execute the newest query to retrieve the years
		$yearArray = $this->returnQueryResult($yearQuery, '', 'pbYear');

		$yearCount = count($yearArray);
		// if there were no years, the following would produce SQL errors
		if ($yearCount) {
			if ($currentPage > $yearCount) {
				// if current page nr exceeds amount of years, replace it with amount of years
				$currentPage = $yearCount;
			}
			// prepend element to make indexes line up with page nrs. DONT use array_unshift
			$yearArray = array_merge(array(0 => 0), $yearArray);
			// finally, set the remaining parameters and expand the original query
			$val = $yearArray[$currentPage];
			// but only on a valid date returned
			if ($val !== '....') {
				$configuration['truePageCount'] = $yearCount;
				$configuration['pageArray'] = $yearArray;
				$query['FROM'] = $yearQuery['FROM'];
				$query['WHERE'] .= '
							AND IF(itf_pbYear.fieldvalue REGEXP (\'' . $regex . '\'),YEAR(itf_pbYear.fieldvalue)=' . $val . ',FALSE)';
			}
		}
	}

	protected function paginateQueryDefault(array $configuration, Query $query) {
		if ( !(isset($configuration['pageLimit']) && isset($configuration['perPageLimit'])) ) {
			// @TODO ___throw exception
		}

		// @TODO ___use or clean up override
		// perPageLimit override through GET var
		/*$overrideVar = $this->controller->getPerPageLimitOverride();
		 if ($overrideVar !== FALSE && isset($pBrowserConfArray['perPageLimitChoice'][$overrideVar])) {
		 $pBrowserConfArray['perPageLimit'] = $pBrowserConfArray['perPageLimitChoice'][$overrideVar];
		 }*/

		// only continue on valid amount of rows per page
		$perPageLimit = (int) $configuration['perPageLimit'];
		if ($perPageLimit <= 0) {
			// @TODO ___throw exception
		}
		$pageLimit = (int) $configuration['pageLimit'];
		if ($pageLimit <= 0) {
			// @TODO ___throw exception
		}
		$currentPage = (int) $configuration['currentPage'];
		if ($currentPage <= 0) {
			// @TODO ___throw exception
		}

		// calculate page amount, and only continue if more than 1

		// @FIX ___note that createStatement() will happen more than once in a single request, and that each time will result
		// in the same workload. So we need to cache it on at least one level, e.g. locally. If useful, even query cache could
		// be applied here. Note that this would mean we can't add LIMIT .. OFFSET to $query without invalidating said cache!
		// So we have to be smart with caching the $queryParts that QueryConfigurator results into, and adding LIMIT there,
		// instead of making LIMIT a part of the cached results. For the time being, we could add LIMIT to $preparedStatement,
		// until we sort out what the definitive method is going to be. It might be smart to wait until all options have been
		// migrated to decosdata, so that we can see if any option needs to be able to alter LIMIT through $query.
		// ALSO: can LIMIT be provided as parameter?
		$preparedStatement = $query->createStatement();

		// @TODO ___execute() may return FALSE on error, we need to catch that
		// @TODO ___we might want a service that does Statement interaction for us, also in the repository.
		// Using our own service for the latter may reduce overhead caused by extbase (repository->query->statement() is an extbase construct anyway, not a FLOW backport)
		$preparedStatement->execute();
		$numRows = $preparedStatement->rowCount();
		$preparedStatement->free();

		// @TODO ___clean up or use
		#$this->totalRows = $numRows;
		$pages = (int) ceil($numRows / $perPageLimit);
		if ($pages > 1) {
			// if the page amount exceeds the allowed limit, set it to the limit
			if ($pageLimit > 0 && $pages > $pageLimit) {
				$pages = $pageLimit;
			}
			// if current page nr exceeds page amount, replace it with last page nr
			if ($currentPage > $pages) {
				$currentPage = $pages;
			}
			// finally, set the remaining parameters and limit the query
			// @TODO ___clean up or use
			#$pBrowserConfArray['truePageCount'] = $pages;
			$offset = $perPageLimit * ($currentPage - 1);
			# @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
			$query->setLimit($perPageLimit, $offset);
		}
	}

}
