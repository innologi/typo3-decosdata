<?php
namespace Innologi\Decosdata\Service;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
/**
 * Search Service
 *
 * Handles searching.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SearchService implements SingletonInterface {
	/* comment from decospublisher, still relevant?
	 *
	 * @TODO if we build a query-cache we could create an eid script
	 * which reads from it to speed up ajax results significantly, while maintaining a fallback
	 * to the original context in case the query cache does not have a valid desired entry
	 */

	/**
	 * @var string
	 */
	protected $searchString;

	/**
	 * @var array
	 */
	protected $searchTerms = [];

	/**
	 * @var boolean
	 */
	protected $active = FALSE;

	/**
	 * @var array
	 */
	protected $characterMask = [' ', "\t", "\n", "\r", "\0", "\x0B"];

	/**
	 * Paginate a Query object
	 *
	 * @param array $configuration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Query $query
	 * @return void
	 */
	public function enableSearch($searchString) {
		$this->active = $this->validateSearchString($searchString);
	}

	/**
	 * Sets QueryOptions to activate a search according to configuration.
	 *
	 * @param array $configuration
	 * @param array $queryOptions
	 * @return array
	 */
	public function configureSearch($configuration, $queryOptions) {
		$matchAll = isset($configuration['matchAllSearchTerms']) && (bool)$configuration['matchAllSearchTerms'];
		$orFilters = [];

		foreach ($this->searchTerms as $i => $searchTerm) {
			$filters = [];
			// multiple sources: OR
			foreach ($configuration['sources'] as $source) {
				// @TODO search content fields?
				// @TODO re-use already joined tables?
				if (isset($source['field'])) {
					$filters[] = [
						'value' => '%' . $searchTerm . '%',
						'operator' => 'LIKE',
						'field' => $source['field']
					];
				}
			}

			if (!empty($filters)) {
				if ($matchAll) {
					// every search term will be enclosed in his own FilterItems call: AND
					$queryOptions[] = [
						'option' => 'FilterItems',
						'args' => ['filters' => $filters]
					];
				} else {
					// every search term will be enclosed in a single FilterItems call: OR
					$orFilters = array_merge($filterCollection, $filters);
				}
			}
		}

		if (!empty($orFilters)) {
			$queryOptions[] = [
				'option' => 'FilterItems',
				'args' => ['filters' => $orFilters]
			];
		}

		return $queryOptions;
	}

	/**
	 * Confirms whether the service was successfully configured and active.
	 *
	 * @return boolean
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * Returns search string.
	 *
	 * @return string
	 */
	public function getSearchString() {
		return $this->searchString;
	}

	/**
	 * Validates search string
	 *
	 * Note that it does not guarantuee a safe string, so don't consider this a free pass for unparameterized queries.
	 *
	 * @param string $searchString
	 * @return boolean
	 */
	protected function validateSearchString($searchString) {
		$valid = FALSE;
		$searchString = trim($searchString, join('', $this->characterMask));
		if (isset($searchString[0])) {
			$searchTerms = explode(' ', $searchString);
			foreach ($searchTerms as $searchTerm) {
				$searchTerm = str_replace($this->characterMask, '', $searchTerm);
				if (isset($searchTerm[0])) {
					$this->searchTerms[] = $searchTerm;
					$valid = TRUE;
				}
			}
		}
		$this->searchString = join(' ', $this->searchTerms);
		return $valid;
	}

}
