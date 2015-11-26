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
/**
 * Query Configurator
 *
 * Provides methods to process, to transform, or to provide default
 * Query configurations.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryConfigurator implements SingletonInterface {
	// @TODO ____SELECT and WHERE's 'field' element is inconsistent with 'FROM's constraint; this is confusing
	// @LOW _lots of hardcoded SQL keywords that should be provided by query provider class from database service
	/**
	 * @var array
	 */
	protected $supportedSorting = array('ASC', 'DESC');

	/**
	 * @var array
	 */
	protected $supportedJoins = array('LEFT', 'RIGHT', 'INNER');

	/**
	 * @var array
	 */
	protected $supportedLogic = array('AND', 'OR');

	/**
	 * @var array
	 */
	protected $specialValues = array('?', 'NULL', 'NOW()');

	/**
	 * @var array
	 */
	protected $supportedOperators = array('=', '<', '>', '<=', '>=', '<>', '!=', '<=>', 'IS', 'NOT', 'REGEXP', 'RLIKE', 'LIKE');

	/**
	 * @var string
	 */
	protected $defaultOperator = '=';

	/**
	 * Provide FROM queryConfiguration.
	 *
	 * $constraints elements should be strings formatted as:
	 * - when compared to foreign field:
	 * 		localField/operator/foreignAlias/foreignField
	 * - when compared to value or function:
	 * 		localField/operator/value
	 *
	 * @param string $table
	 * @param string $alias
	 * @param string $joinType
	 * @param array $constraints
	 * @param boolean $matchAll
	 * @return array
	 */
	public function provideFrom($table, $alias = '', $joinType = '', array $constraints = array(), $matchAll = TRUE) {
		$from = array(
			'table' => $table
		);
		if (isset($alias[0])) {
			$from['alias'] = $alias;
		}
		if (isset($joinType[0])) {
			$from['joinType'] = $joinType;
			if (!empty($constraints)) {
				$from['constraints'] = array();
				foreach ($constraints as $constraint) {
					list($localField, $operator, $var1, $var2) = explode('/', $constraint, 4);
					$c = array(
						'localField' => $localField,
						'operator' => $operator
					);
					// note the distinction being made only if $var2 exists
					if (isset($var2)) {
						$c['foreignAlias'] = $var1;
						$c['foreignField'] = $var2;
					} else {
						$c['value'] = $var1;
					}
					$from['constraints'][] = $c;
				}
				$from['matchAll'] = $matchAll;
			}
		}
		return $from;
	}

	/**
	 * Provide WHERE condition configuration. This variant assumes no checks are needed
	 * on the operator and value, and is therefore faster than provideWhereSafe().
	 * Should only be used with hardcoded or previously verified argument values.
	 *
	 * Note that it only returns a single WHERE condition, you have to provide further
	 * logic for WHERE configuration yourself, for now.
	 *
	 * @param string $field
	 * @param string $operator
	 * @param string $value
	 * @return array
	 * @see QueryConfigurator::provideWhereConditionSafe()
	 */
	public function provideWhereConditionUnsafe($field, $operator, $value) {
		return array(
			'field' => $field,
			'operator' => $operator,
			'value' => $value
		);
	}

	/**
	 * Provide WHERE condition configuration. This variant checks if operator is
	 * supported and if value needs treatment for parameterization, before passing
	 * them along to provideWhereUnsafe(). This variant is useful for constraints
	 * that contain user-input.
	 *
	 * There are no checks on $field, because it should NEVER be derived from
	 * user-input.
	 *
	 * If the $parameters reference did not previously exist, it will afterwards
	 * if parameterization is applied.
	 *
	 * Note that it only returns a single WHERE condition, you have to provide further
	 * logic for WHERE configuration yourself, for now.
	 *
	 * @param string $field
	 * @param string $operator
	 * @param string $value
	 * @param array $parameters
	 * @return array
	 * @see QueryConfigurator::provideWhereConditionUnsafe()
	 * @see QueryConfigurator::resolveComparisonValue()
	 */
	public function provideWhereConditionSafe($field, $operator, $value, &$parameters) {
		if (!is_array($parameters)) {
			$parameters = array();
		}
		return $this->provideWhereConditionUnsafe(
			$field,
			$this->resolveOperator($operator),
			$this->resolveComparisonValue($value, $parameters)
		);
	}

	/**
	 * Transforms a valid queryConfiguration to actual SQL into &$queryParts and &$parameterParts.
	 * Both references can be used to form a Query object.
	 *
	 * @param array $queryConfiguration
	 * @param array &$queryParts
	 * @param array &$parameterParts
	 * @return void
	 */
	public function transformConfiguration(array $queryConfiguration, array &$queryParts, array &$parameterParts) {
		$qParts = array();
		$glue = array(
			'SELECT' => ',',
			'FROM' => "\n",
			'WHERE' => ' AND ',
			'GROUPBY' => ',',
			'ORDERBY' => ','
		);

		// Query configurations are separated by IDs that represent their content field.
		// This effectively gives us control to alter specific configuration parts from other
		// parts (e.g. options), and to impose the order given by the content configuration.
		foreach ($queryConfiguration as $id => $subConfiguration) {
			$select = array();
			foreach ($subConfiguration as $parts) {
				foreach ($parts as $part => $partConfig) {
					switch ($part) {
						case 'SELECT':
							$select[] = $this->transformSelect($partConfig);
							break;
						case 'FROM':
							// multiple from configurations per id possible
							foreach ($partConfig as $fromConfig) {
								$qParts[$part][] = $this->transformFrom($fromConfig);
							}
							break;
						case 'WHERE':
							$qParts[$part][] = $this->transformWhere($partConfig);
							break;
						case 'GROUPBY':
							$qParts[$part][$partConfig['priority']] = $id;
							break;
						case 'ORDERBY':
							$qParts[$part][$partConfig['priority']] = $this->transformOrderBy($partConfig, $id);
							break;
						case 'PARAMETER':
							foreach ($partConfig as $subPart => $parameters) {
								$parameterParts[$subPart] = array_merge($parameterParts[$subPart], $parameters);
							}
					}
				}
			}

			// applies concatting SELECT per id, to form a single alias per content field
			if (!empty($select)) {
				$qParts['SELECT'][] = $this->concatSelect($select, $id);
			}
		}

		// these are stored by priority, so we can determine the order by sorting on key
		if (isset($qParts['GROUPBY'])) {
			ksort($qParts['GROUPBY']);
		}
		if (isset($qParts['ORDERBY'])) {
			ksort($qParts['ORDERBY']);
		}

		// joins all parts' elements to part strings
		foreach ($qParts as $part => $q) {
			if (!isset($queryParts[$part])) {
				$queryParts[$part] = '';
			}
			$queryParts[$part] .= join($glue[$part], $q);
		}
	}

	/**
	 * Transform a SELECT configuration
	 *
	 * - Requires 'field' element
	 * - Supports 'wrap' element containing pipe-character "|"
	 *
	 * @param array $configuration
	 * @return string
	 * @throws Exception\MissingConfigurationProperty
	 */
	protected function transformSelect(array $configuration) {
		if (!isset($configuration['field'])) {
			throw new Exception\MissingConfigurationProperty(1448552576, array(
				'SELECT', 'field', json_encode($configuration)
			));
		}

		if (isset($configuration['wrap'])) {
			foreach($configuration['wrap'] as $wrap) {
				$configuration['field'] = str_replace('|', $configuration['field'], $wrap);
			}
		}
		$select = $configuration['field'];
		return $select;
	}

	/**
	 * Concats a select string to form a single alias for one or more select fields.
	 *
	 * @param array $select
	 * @param string $alias
	 * @return string
	 */
	protected function concatSelect(array $select, $alias) {
		// @TODO ___make concat arg 1 configurable?
		return (count($select) > 1
			? 'CONCAT_WS(\' \',' . join(',', $select) . ')'
			: $select[0]) . ' AS ' . $alias;
	}

	/**
	 * Transforms a FROM configuration. Note that each configuration
	 * can refer to only a single 'table', even though e.g. MySQL supports
	 * multiple per JOIN and decospublisher did this too. This is by design,
	 * to reduce configuration complexity. Just provide multiple FROM
	 * configurations if there are multiple tables to be joined.
	 *
	 * - Requires 'table' element
	 * - Supports 'joinType' element
	 * - Supports 'alias' element
	 * - Supports 'constraints' array and optional 'matchAll' boolean
	 *
	 * A contraints element array:
	 * - Requires 'localField' element
	 * - Requires 'operator' element
	 * - Requires either a 'value' element or 'foreignAlias' + 'foreignField' elements
	 *
	 * @param array $configuration
	 * @return string
	 * @throws Exception\MissingConfigurationProperty
	 * @throws Exception\UnsupportedFeatureType
	 */
	protected function transformFrom(array $configuration) {
		if (!isset($configuration['table'])) {
			throw new Exception\MissingConfigurationProperty(1448552598, array(
				'FROM', 'table', json_encode($configuration)
			));
		}

		$from = $configuration['table'];
		if (isset($configuration['alias'])) {
			$from .= ' ' . $configuration['alias'];
		} else {
			// fallback for constraints below
			$configuration['alias'] = $configuration['table'];
		}

		if (isset($configuration['joinType'])) {
			if (!in_array($configuration['joinType'], $this->supportedJoins, TRUE)) {
				throw new Exception\UnsupportedFeatureType(1448552612, array(
					'TABLE JOIN TYPE', $configuration['joinType'], join('/', $this->supportedJoins)
				));
			}
			$from = $configuration['joinType'] . ' JOIN ' . $from;

			// constraints are applied on a join, otherwise they're ignored
			if (isset($configuration['constraints'])) {
				$constraintType = isset($configuration['matchAll']) && (bool) $configuration['matchAll'] ? 'AND' : 'OR';
				$on = array();
				foreach ($configuration['constraints'] as $constraint) {
					if ( !(isset($constraint['localField']) && isset($constraint['operator'])) ) {
						throw new Exception\MissingConfigurationProperty(1448552629, array(
							'FROM', 'constraint.localField/operator', json_encode($configuration)
						));
					}
					$c = $configuration['alias'] . '.' . $constraint['localField'] . ' ' . $constraint['operator'] . ' ';
					if (isset($constraint['value'])) {
						$c .= $constraint['value'];
					} elseif (isset($constraint['foreignAlias']) && isset($constraint['foreignField'])) {
						$c .= $constraint['foreignAlias'] . '.' . $constraint['foreignField'];
					} else {
						throw new Exception\MissingConfigurationProperty(1448552652, array(
							'FROM', 'constraint.foreignAlias/foreignField/value', json_encode($configuration)
						));
					}
					$on[] = $c;
				}
				$from .= ' ON (' . join(' ' . $constraintType . ' ', $on) . ')';
			}
		}

		return $from;
	}

	/**
	 * Transforms a WHERE configuration recursively.
	 *
	 * Examples of supported $conditions structures:
	 *
	 * 1) (AND =>) array(
	 * 		array(
	 * 			'field' => 'field',
	 * 			'operator' => '=',
	 * 			'value' => '?'
	 * 		)
	 * 	)
	 * 2) (AND =>) array(
	 * 		array(
	 * 			OR => array(
	 * 				array(field/operator/value),
	 * 				array(
	 * 					AND => array(
	 * 						array(field/operator/value),
	 * 						array(field/operator/value)
	 *					)
	 * 				)
	 * 			)
	 * 		)
	 * 	)
	 *
	 * @param array $conditions
	 * @param string $logic AND or OR
	 * @return string
	 * @throws Exception\UnsupportedFeatureType
	 * @throws Exception\MissingConfigurationProperty
	 */
	protected function transformWhere(array $conditions, $logic = 'AND') {
		if (!in_array($logic, $this->supportedLogic, TRUE)) {
			throw new Exception\UnsupportedFeatureType(1448552681, array(
				'LOGICAL OPERATOR', $logic, join('/', $this->supportedLogic)
			));
		}

		// $conditions is always multi-dimensional!
		$where = array();
		foreach ($conditions as $configuration) {
			// $configuration contains a field/operator/value
			if (count($configuration) > 1) {
				if ( !(isset($configuration['field']) && isset($configuration['operator']) && isset($configuration['value'])) ) {
					throw new Exception\MissingConfigurationProperty(1448552699, array(
						'WHERE', 'field/operator/value', json_encode($conditions)
					));
				}
				// @TODO ___is this safe enough? provideWhereConditionSafe() handles some validation, but shouldn't it be done from here instead? Also, how would we verify field?
				$where[] = $configuration['field'] . ' ' . $configuration['operator'] . ' ' . $configuration['value'];
			// .. or it contains a single new conditions array with AND/OR key for the next recursion
			} else {
				$where[] = '(' . $this->transformWhere(current($configuration), key($configuration)) . ')';
			}
		}

		return join(' ' . $logic . ' ', $where);
	}

	/**
	 * Transform an ORDERBY configuration, note that the actual sorting
	 * happens on $alias.
	 *
	 * - Requires 'priority' element
	 * - Supports 'sort' element to set order
	 *
	 * @param array $configuration
	 * @param string $alias
	 * @return string
	 * @throws Exception\MissingConfigurationProperty
	 * @throws Exception\UnsupportedFeatureType
	 */
	protected function transformOrderBy(array $configuration, $alias) {
		if (!isset($configuration['priority'])) {
			throw new Exception\MissingConfigurationProperty(1448552721, array(
				'ORDERBY', 'priority', json_encode($configuration)
			));
		}

		$orderBy = $alias;
		if (isset($configuration['sort'])) {
			if (!in_array($configuration['sort'], $this->supportedSorting, TRUE)) {
				throw new Exception\UnsupportedFeatureType(1448552741, array(
					'SORTING ORDER', $configuration['sort'], join('/', $this->supportedSorting)
				));
			}
			$orderBy .= ' ' . $configuration['sort'];
		}
		return $orderBy;
	}

	/**
	 * Resolves an operator for queryConfiguration.
	 *
	 * @param string $operator (optional)
	 * @return string
	 * @throws Exception\UnsupportedFeatureType
	 */
	protected function resolveOperator($operator = NULL) {
		if ($operator === NULL) {
			return $this->defaultOperator;
		}

		$operators = explode(' ', strtoupper($operator));
		foreach ($operators as $operator) {
			if (!in_array($operator, $this->supportedOperators, TRUE)) {
				throw new Exception\UnsupportedFeatureType(1448552756, array(
					'COMPARISON OPERATOR', $operator, join('/', $this->supportedOperators)
				));
			}
		}
		return join(' ', $operators);
	}

	/**
	 * Resolves a comparison value for queryConfiguration, parameterizing
	 * it if not in the special value list. (in which case it returns ? and
	 * provides $parameters with the actual value)
	 *
	 * @param string $value
	 * @param array &$parameters
	 * @return string
	 * @throws Exception\CannotResolveComparisonValue
	 */
	protected function resolveComparisonValue($value, array &$parameters) {
		if ($value === NULL) {
			throw new Exception\CannotResolveComparisonValue(1448552781);
		}

		$value = strtoupper($value);
		if (in_array($value, $this->specialValues, TRUE)) {
			return $value;
		}

		$parameters[] = $value;
		return '?';
	}
}
