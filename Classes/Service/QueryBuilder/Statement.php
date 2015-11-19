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
use TYPO3\CMS\Core\Database\PreparedStatement;
/**
 * Statement Object
 *
 * Improves upon TYPO3's own PreparedStatement:
 *
 * - by offering a method to retrieve the query directly for logging
 * and debugging purposes.
 *
 * - by adding support for arrays. Although I can supply a statement
 * through Extbase Repositories by string, which offers support for
 * array parameters, this was deprecated since 6.2 if using parameters.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Statement extends PreparedStatement {

	/**
	 * Returns query
	 *
	 * @return string
	 */
	public function getQuery() {
		// @TODO _how to return a query that has its parameters replaced?
		return $this->query;
	}



	/**
	 * @var integer
	 */
	const PARAM_ARRAY = 201511181983;

	/**
	 * Guesses the type of a given value.
	 *
	 * -- Detects arrays, otherwise falls back to parent method
	 *
	 * @param mixed $value
	 * @return integer One of the \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_* constants
	 */
	protected function guessValueType($value) {
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
	 * @param array $parameterValues
	 * @param array $precompiledQueryParts
	 * @return void
	 */
	protected function convertNamedPlaceholdersToQuestionMarks(&$query, array &$parameterValues, array &$precompiledQueryParts) {
		$queryPartsCount = count($precompiledQueryParts['queryParts']);
		$newParameterValues = array();
		$hasNamedPlaceholders = FALSE;

		if ($queryPartsCount === 0) {
			$hasNamedPlaceholders = $this->hasNamedPlaceholders($query);
			if ($hasNamedPlaceholders) {
				$query = $this->tokenizeQueryParameterMarkers($query, $parameterValues);
			}
		} elseif (count($parameterValues) > 0) {
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
		$matches = array();
		// END ALTERATION -->
		if ($hasNamedPlaceholders) {
			if ($queryPartsCount === 0) {
				// Convert named placeholders to standard question mark placeholders
				$quotedParamWrapToken = preg_quote($this->parameterWrapToken, '/');
				while (preg_match(
					'/' . $quotedParamWrapToken . '(.*?)' . $quotedParamWrapToken . '/',
					$query,
					$matches
					)) {
						$key = $matches[1];

						// <-- BEGIN ALTERATION
						$replacement = '?';
						$par = $parameterValues[$key];
						if ($par['type'] !== self::PARAM_ARRAY) {
							// not an array; behave as parent method
							$newParameterValues[] = $par;
						}  else {
							// array; add each value as a new parameter
							// assume all values are of same type
							$type = parent::guessValueType($par['value'][0]);
							$parts = array();
							foreach ($par['value'] as $v) {
								$parts[] = $replacement;
								$newParameterValues[] = array('value' => $v, 'type' => $type);
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
							$query,
							1
						);
					}
			}

			$parameterValues = $newParameterValues;
		}
	}

}