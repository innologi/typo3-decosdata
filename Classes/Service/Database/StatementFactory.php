<?php

namespace Innologi\Decosdata\Service\Database;

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
use Innologi\Decosdata\Exception\MissingQueryPart;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Statement Factory
 *
 * Enables easy creation of Statement objects from QueryParts and parameters.
 * Note that this class does NOT validate SQL syntax.
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
class StatementFactory implements SingletonInterface
{
    /**
     * Creates a Statement object
     *
     * @return Statement
     */
    public function create(array $queryParts, array $parameters)
    {
        /** @var Statement $statement */
        $statement = GeneralUtility::makeInstance(
            Statement::class,
            $this->mergeQueryParts($queryParts),
            '',
        );
        $statement->bindValues($parameters);
        return $statement;
    }

    /**
     * Merges QueryParts into a single query string.
     *
     * @return string
     * @throws \Innologi\Decosdata\Exception\MissingQueryPart
     */
    protected function mergeQueryParts(array $queryParts)
    {
        if (!(isset($queryParts['SELECT'][0]) && isset($queryParts['FROM'][0]))) {
            throw new MissingQueryPart(1449155154, ['SELECT, FROM', json_encode($queryParts)]);
        }

        $query = 'SELECT ' . $queryParts['SELECT'] . PHP_EOL .
            'FROM ' . $queryParts['FROM'];

        if (isset($queryParts['WHERE'][0])) {
            $query .= PHP_EOL . 'WHERE ' . $queryParts['WHERE'];
        }
        if (isset($queryParts['GROUPBY'][0])) {
            $query .= PHP_EOL . 'GROUP BY ' . $queryParts['GROUPBY'];
        }
        if (isset($queryParts['ORDERBY'][0])) {
            $query .= PHP_EOL . 'ORDER BY ' . $queryParts['ORDERBY'];
        }
        # @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
        #if (isset($queryParts['LIMIT'][0])) {
        #	$query .= PHP_EOL . 'LIMIT ' . $queryParts['LIMIT'];
        #}
        if ($this->limit !== null) {
            $query .= PHP_EOL . 'LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $query .= ' OFFSET ' . $this->offset;
            }
        }
        ####################
        return $query;
    }

    # @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
    protected $limit;
    protected $offset;
    public function setLimit($limit = null, $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }
}
