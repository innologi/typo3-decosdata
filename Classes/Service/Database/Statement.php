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
use TYPO3\CMS\Core\Database\PreparedStatement;

/**
 * Statement Object
 *
 * Improves upon TYPO3's own PreparedStatement:
 *
 * - by offering a method to retrieve the query directly for logging
 * and debugging purposes.
 *
 * - by adding workarounds for PHP MySQLi bugs during step-debugging
 *
 * - by adding support for arrays. Although I can supply a statement
 * through Extbase Repositories by string, which offers support for
 * array parameters, this was deprecated since 6.2 if using parameters.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @extensionScannerIgnoreLine TYPO3_DB-usage needs a rewrite anyway once this ext goes standalone
 */
class Statement extends PreparedStatement implements \Stringable
{
    /**
     * @var string
     */
    protected $processedQuery;

    /**
     * @var array
     */
    protected $usedParameters = [];

    /**
     * Returns processed query for debugging.
     *
     * This Query is NOT ENTIRELY SAFE to use.
     */
    public function getProcessedQuery(): string
    {
        // the isset statement part is to ensure we can only do this
        if ($this->processedQuery === null) {
            $parameters = !empty($this->parameters) ? $this->parameters : $this->usedParameters;
            $precompiledQueryParts = $this->precompiledQueryParts;
            $tempQuery = $this->query;
            $this->convertNamedPlaceholdersToQuestionMarks($tempQuery, $parameters, $precompiledQueryParts);
            $queryArray = explode('?', (string) $tempQuery);
            foreach ($parameters as $index => $parameter) {
                $queryArray[$index] .= '\'' . ($parameter['value'] ?? '') . '\'';
            }
            $this->processedQuery = join('', $queryArray);
        }
        return $this->processedQuery;
    }



    /**
     * Bug in MySQLi PHP driver causes an issue when step-debugging around statement->close(). Doing an unset() lets us work around the issue.
     *
     * @see https://stackoverflow.com/questions/25377030/mysqli-xdebug-breakpoint-after-closing-statment-result-in-many-warnings
     * @see http://bugs.xdebug.org/view.php?id=900
     * @see http://bugs.xdebug.org/view.php?id=1071
     * @see https://bugs.php.net/bug.php?id=63486
     * @see https://bugs.php.net/bug.php?id=60778
     *
     * Similar issue with statement->affected_rows we noticed creating migration/importer, also only when step-debugging.
     * @see https://bugs.php.net/bug.php?id=67348
     */
    public function free()
    {
        $this->statement->close();
        unset($this->statement);
    }

    /**
     * Necessary for getProcessedQuery() to work
     *
     * {@inheritDoc}
     * @see \TYPO3\CMS\Core\Database\PreparedStatement::execute()
     */
    public function execute(array $input_parameters = [])
    {
        $this->usedParameters = $this->parameters;
        return parent::execute($input_parameters);
    }


    /**
     * @var integer
     */
    public const PARAM_ARRAY = 201511181983;

    /**
     * Guesses the type of a given value.
     *
     * -- Detects arrays, otherwise falls back to parent method
     *
     * @param mixed $value
     * @return integer One of the \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_* constants
     */
    protected function guessValueType($value)
    {
        return is_array($value) ? self::PARAM_ARRAY : parent::guessValueType($value);
    }

    /**
     * Converts named placeholders into question mark placeholders in a query.
     *
     * -- Unfortunately, this method had to be copied entirely from TYPO3 6.2.15, then altered where marked as such.
     *
     * @author Xavier Perseguers <typo3@perseguers.ch>
     * @license https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License, version 2 or later
     *
     * @param string $query
     */
    protected function convertNamedPlaceholdersToQuestionMarks(&$query, array &$parameterValues, array &$precompiledQueryParts)
    {
        $queryPartsCount = isset($precompiledQueryParts['queryParts']) && is_array($precompiledQueryParts['queryParts']) ? count($precompiledQueryParts['queryParts']) : 0;
        $newParameterValues = [];
        $hasNamedPlaceholders = false;

        if ($queryPartsCount === 0) {
            $hasNamedPlaceholders = $this->hasNamedPlaceholders($query);
            if ($hasNamedPlaceholders) {
                $query = $this->tokenizeQueryParameterMarkers($query, $parameterValues);
            }
        } elseif (!empty($parameterValues)) {
            $hasNamedPlaceholders = !is_int(key($parameterValues));
            if ($hasNamedPlaceholders) {
                for ($i = 1; $i < $queryPartsCount; $i += 2) {
                    $key = $precompiledQueryParts['queryParts'][$i];
                    $precompiledQueryParts['queryParts'][$i] = '?';
                    $newParameterValues[] = $parameterValues[$key];
                }
            }
        }

        // <-- BEGIN ALTERATION
        $matches = [];
        // END ALTERATION -->
        if ($hasNamedPlaceholders) {
            if ($queryPartsCount === 0) {
                // Convert named placeholders to standard question mark placeholders
                $quotedParamWrapToken = preg_quote((string) $this->parameterWrapToken, '/');
                while (preg_match(
                    '/' . $quotedParamWrapToken . '(.*?)' . $quotedParamWrapToken . '/',
                    (string) $query,
                    $matches,
                )) {
                    $key = $matches[1];

                    // <-- BEGIN ALTERATION
                    $replacement = '?';
                    $par = $parameterValues[$key];
                    if ($par['type'] !== self::PARAM_ARRAY) {
                        // not an array; behave as parent method
                        $newParameterValues[] = $par;
                    } else {
                        // array; add each value as a new parameter
                        // assume all values are of same type
                        $type = parent::guessValueType($par['value'][0]);
                        $parts = [];
                        foreach ($par['value'] as $v) {
                            $parts[] = $replacement;
                            $newParameterValues[] = [
                                'value' => $v,
                                'type' => $type,
                            ];
                        }
                        // also provides the necessary enclosing parentheses
                        $replacement = '(' . join(',', $parts) . ')';
                    }
                    // END ALTERATION -->

                    $query = preg_replace(
                        '/' . $quotedParamWrapToken . $key . $quotedParamWrapToken . '/',
                        // <-- BEGIN ALTERATION
                        $replacement,
                        // END ALTERATION -->
                        (string) $query,
                        1,
                    );
                }
            }

            $parameterValues = $newParameterValues;
        }
    }

    public function __toString(): string
    {
        // TYPO3v9 Extbase Query considers this Statement an actual query, because it's not an instance of:
        // - TYPO3 QueryBuilder
        // - Doctrine DBAL Statement
        // @TODO although this method provides a working fallback, we should consider replacing our Statement with either of the above
        return $this->getProcessedQuery();
    }
}
