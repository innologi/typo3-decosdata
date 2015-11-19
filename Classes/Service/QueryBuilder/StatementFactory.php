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
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
/**
 * Statement Factory
 *
 * Enables easy creation of Statement objects from QueryParts and parameters.
 * Note that this class does NOT validate SQL syntax. Creating from a
 * Query will provide some validation through QueryConfigurator.
 *
 * Query parts are expected to contain the following elements:
 * - SELECT
 * - FROM
 * Query parts also support the following optional elements:
 * - WHERE
 * - GROUPBY
 * - ORDERBY
 * - LIMIT
 *
 * Note that there are no parameters supported in ORDERBY!
 * The database engines supported by this extension do not
 * support this feature.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class StatementFactory implements SingletonInterface {

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\QueryConfigurator
	 * @inject
	 */
	protected $queryConfigurator;

	/**
	 * Creates a Statement object
	 *
	 * @param array $queryParts
	 * @param array $parameters
	 * @return Statement
	 */
	public function create(array $queryParts, array $parameters) {
		/** @var $statement Statement */
		$statement = GeneralUtility::makeInstance(
			Statement::class,
			$this->mergeQueryParts($queryParts),
			''
		);
		$statement->bindValues($parameters);
		return $statement;
	}

	/**
	 * Creates a Statement object from a Query.
	 *
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Query $query
	 * @return Statement
	 */
	public function createFromQuery(Query $query) {
		return $this->create(
			$this->queryConfigurator->transformConfiguration($query),
			$query->getParameters()
		);
	}

	/**
	 * Merges QueryParts into a single query string.
	 *
	 * @param array $queryParts
	 * @return string
	 * @throws Exception\MissingQueryPart
	 */
	protected function mergeQueryParts(array $queryParts) {
		if ( !(isset($queryParts['SELECT'][0]) && isset($queryParts['FROM'][0])) ) {
			throw new Exception\MissingQueryPart(array('SELECT, FROM'));
		}

		$query = 'SELECT ' . $queryParts['SELECT'] . "\n" .
			'FROM ' . $queryParts['FROM'];

		if (isset($queryParts['WHERE'][0])) {
			$query .= "\n" . 'WHERE ' . $queryParts['WHERE'];
		}
		if (isset($queryParts['GROUPBY'][0])) {
			$query .= "\n" . 'GROUP BY ' . $queryParts['GROUPBY'];
		}
		if (isset($queryParts['ORDERBY'][0])) {
			$query .= "\n" . 'ORDER BY ' . $queryParts['ORDERBY'];
		} else {
			// NULL prevents potential filesorts through GROUP BY sorting, when no ORDER BY was given
			$query .= "\n" . 'ORDER BY NULL';
		}
		if (isset($queryParts['LIMIT'][0])) {
			$query .= "\n" . 'LIMIT ' . $queryParts['LIMIT'];
		}

		return $query;
	}

}
