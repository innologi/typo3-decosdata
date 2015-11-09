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
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * Query Factory
 *
 * Enables easy creation of Query objects from Query- and Parameter- parts.
 * Note that parts nor the resulting Query object are validated on SQL
 * syntax.
 *
 * Query parts are expected to contain the following elements:
 * - SELECT
 * - FROM
 * Query parts also support the following optional elements:
 * - WHERE
 * - GROUPBY
 * - ORDERBY
 * - LIMIT
 * Parameter parts support the following optional elements:
 * - SELECT
 * - FROM
 * - WHERE
 * - GROUPBY
 * - LIMIT
 *
 * (Note that parameter parts does not support ORDERBY, as there is no
 * support for parameterized ORDER BY elements in any database engine
 * supported by this extension)
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryFactory implements SingletonInterface {

	/**
	 * Creates a query object
	 *
	 * @param array $queryParts
	 * @param array $parameterParts
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query
	 */
	public function create(array $queryParts, array $parameterParts) {
		return GeneralUtility::makeInstance(
			'Innologi\\Decosdata\\Service\\QueryBuilder\\Query',
			$this->mergeQueryParts($queryParts),
			$this->mergeParameterParts($parameterParts)
		);
	}

	/**
	 * Merges query parts into a single query string
	 *
	 * @param array $queryParts
	 * @return string
	 * @throws Exception\MissingQueryPart
	 */
	protected function mergeQueryParts(array $queryParts) {
		if ( !(isset($queryParts['SELECT'][0]) && isset($queryParts['FROM'][0])) ) {
			throw new Exception\MissingQueryPart(array('SELECT, FROM'));
		}

		$query = 'SELECT ' . $queryParts['SELECT'] . '
			FROM ' . $queryParts['FROM'];

		if (isset($queryParts['WHERE'][0])) {
			$query .= '
				WHERE ' . $queryParts['WHERE'];
		}
		if (isset($queryParts['GROUPBY'][0])) {
			$query .= '
				GROUP BY ' . $queryParts['GROUPBY'];
		}
		if (isset($queryParts['ORDERBY'][0])) {
			$query .= '
				ORDER BY ' . $queryParts['ORDERBY'];
		} else {
			// NULL prevents potential filesorts through GROUP BY sorting, when no ORDER BY was given
			$query .= '
				ORDER BY NULL';
		}
		if (isset($queryParts['LIMIT'][0])) {
			$query .= '
				LIMIT ' . $queryParts['LIMIT'];
		}

		return $query;
	}

	/**
	 * Merges parameter parts into a single parameter array
	 *
	 * @param array $parameterParts
	 * @return array
	 */
	protected function mergeParameterParts(array $parameterParts) {
		$parameters = array();
		$supportedParts = array('SELECT', 'FROM', 'WHERE', 'GROUPBY', 'LIMIT');
		foreach ($supportedParts as $part) {
			if (isset($parameterParts[$part])) {
				$parameters = array_merge($parameters, $parameterParts[$part]);
			}
		}
		return $parameters;
	}

}
