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
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\Select;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\From;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintCollection;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintByValue;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintByField;
/**
 * Query Configurator
 *
 * Provides methods to transform Query objects to Query Parts.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryConfigurator implements SingletonInterface {
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
	protected $specialValues = array('NULL', 'NOW()');

	/**
	 * @var array
	 */
	protected $supportedOperators = array('=', '<', '>', '<=', '>=', '<>', '!=', '<=>', 'IS', 'NOT', 'REGEXP', 'RLIKE', 'LIKE', 'IN');

	/**
	 * @var array
	 */
	protected $addParameters = array();

	/**
	 * @var integer
	 */
	protected $parameterCount = 0;

	/**
	 * Transforms a Query object to actual SQL Query Parts.
	 *
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Query $query
	 * @return array
	 */
	public function transformConfiguration(Query $query) {
		$queryParts = array();
		$glue = array(
			'SELECT' => ',',
			'FROM' => "\n",
			'WHERE' => ' AND ',
			'GROUPBY' => ',',
			'ORDERBY' => ','
		);
		$this->addParameters = array();

		// Query consists of QueryContent with an ID that represents content alias.
		// This object-approach effectively gives us control to alter specific configuration parts from
		// other parts (e.g. options), and to impose a specific order of parts before I'm left to fiddle
		// with hacky string manipulation as was the case in tx_decospublisher.

		/** @var $queryContent \Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent */
		foreach ($query as $id => $queryContent) {
			$concatSelect = array();
			/** @var $queryField \Innologi\Decosdata\Service\QueryBuilder\Query\QueryField */
			foreach ($queryContent as $queryField) {
				// @LOW ___this doesn't look right, I have dedicated methods which should isolate everything pertaining to the object, but I still have to check specific values before executing them
				$select = $queryField->getSelect();
				if ($select->getField() !== NULL) {
					$concatSelect[] = $this->transformSelect($select);
				}
				$fromArray = $queryField->getFromAll();
				foreach ($fromArray as $from) {
					$queryParts['FROM'][] = $this->transformFrom($from);
				}
				$where = $queryField->getWhere();
				if ($where->getConstraint() !== NULL) {
					$queryParts['WHERE'][] = $this->transformConstraint($where->getConstraint(), 'WHERE');
				}
				$orderBy = $queryField->getOrderBy();
				if ($orderBy->getPriority() !== NULL) {
					$queryParts['ORDERBY'][$orderBy->getPriority()] = $this->transformOrderBy($orderBy);
				}
			}

			// applies concatting SELECT per id, to form a single alias per content field
			if (!empty($concatSelect)) {
				$queryParts['SELECT'][] = $this->concatSelect($concatSelect, $id);
			}

			$groupBy = $queryContent->getGroupBy();
			if ($groupBy->getPriority() !== NULL) {
				$queryParts['GROUPBY'][$groupBy->getPriority()] = $id;
			}
			$orderBy = $queryContent->getOrderBy();
			if ($orderBy->getPriority() !== NULL) {
				$queryParts['ORDERBY'][$orderBy->getPriority()] = $this->transformOrderBy($orderBy, $id);
			}
		}

		// these are stored by priority, so we can determine the order by sorting on key
		if (isset($queryParts['GROUPBY'])) {
			ksort($queryParts['GROUPBY']);
		} else {
			// If no group by, then we'll prevent duplicates by using the DISTINCT keyword.
			// There are MANY cases where either a DISTINCT is required (on import/mm joins),
			// or the effort to exclude it automatically has no effect on the item-join.
			// (ORDER BY/GROUP BY/pagebrowser all result in 'use temporary/filesort')
			$queryParts['SELECT'][0] = 'DISTINCT ' . $queryParts['SELECT'][0];
		}
		if (isset($queryParts['ORDERBY'])) {
			ksort($queryParts['ORDERBY']);
		} else {
			// NULL prevents potential filesorts through GROUP BY sorting, when no ORDER BY was given
			$query .= "\n" . 'ORDER BY NULL';
		}

		// add parameter:values that were not yet parameterized
		if (!empty($this->addParameters)) {
			foreach ($this->addParameters as $key => $value) {
				$query->addParameter($key, $value);
			}
		}

		// joins all parts' elements to part strings
		foreach ($queryParts as $part => $q) {
			$queryParts[$part] = join($glue[$part], $q);
		}

		return $queryParts;
	}

	/**
	 * Transform a SELECT object
	 *
	 * - Requires 'field' element
	 * - Requires 'tableAlias' element
	 * - Supports 'wrap' element containing pipe-character "|"
	 *
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Part\Select $select
	 * @return string
	 * @throws Exception\MissingConfigurationProperty
	 */
	protected function transformSelect(Select $select) {
		$field = $select->getField();
		if (!isset($field[0])) {
			throw new Exception\MissingConfigurationProperty(1448552576, array(
				'SELECT', 'field', json_encode($select)
			));
		}

		$tableAlias = $select->getTableAlias();
		if (!isset($tableAlias[0])) {
			throw new Exception\MissingConfigurationProperty(1448622360, array(
				'SELECT', 'tableAlias', json_encode($select)
			));
		}

		return $this->transformWrap($tableAlias . '.' . $field, $select->getWrap(), $select->getWrapDivider());
	}

	/**
	 * Joins multiple SELECT fields under a single alias
	 * through concat mysql function(s).
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
	 * Transforms a FROM object. Note that each configuration can refer
	 * to only a single 'table', even though e.g. MySQL supports multiple
	 * per JOIN and tx_decospublisher did this too. This is by design,
	 * to reduce configuration complexity. Just provide multiple FROM
	 * configurations if there are multiple tables to be joined.
	 *
	 * - Requires 'tables' element
	 * - Supports 'joinType' element
	 * - Supports a 'constraint'
	 *
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Part\From $from
	 * @return string
	 * @throws Exception\MissingConfigurationProperty
	 * @throws Exception\UnsupportedFeatureType
	 */
	protected function transformFrom(From $from) {
		$tables = $from->getTables();
		if (!isset($tables) || empty($tables)) {
			throw new Exception\MissingConfigurationProperty(1448552598, array(
				'FROM', 'tables', json_encode($from)
			));
		}

		$formatTables = array();
		foreach ($tables as $alias => $table) {
			$formatTables[] = $table . ' ' . $alias;
		}
		$string = '(' . join(',', $formatTables) . ')';

		$joinType = $from->getJoinType();
		// @LOW _if joinType is NULL, transformConfiguration will still join the table with "\n" and not with a comma..
		if ($joinType !== NULL) {
			if (!in_array($joinType, $this->supportedJoins, TRUE)) {
				throw new Exception\UnsupportedFeatureType(1448552612, array(
					'TABLE JOIN TYPE', $joinType, join('/', $this->supportedJoins)
				));
			}
			$string = $joinType . ' JOIN ' . $string;
			$constraint = $from->getConstraint();
			// constraints are only applied on a join, otherwise they're ignored
			if ($constraint !== NULL) {
				$string .= ' ON ' . $this->transformConstraint($constraint, 'FROM');
			}
		}

		return $string;
	}

	/**
	 * Transforms a Constraint object. Constraints can be part of:
	 * - a FROM object
	 * - a WHERE object
	 * - a ConstraintCollection object
	 *
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface $constraint
	 * @param string $queryPart
	 * @throws Exception\UnsupportedFeatureType
	 * @throws Exception\MissingConfigurationProperty
	 * @return string
	 */
	protected function transformConstraint(ConstraintInterface $constraint, $queryPart = '') {
		if ($constraint instanceof ConstraintCollection) {
			$logic = $constraint->getLogic();
			if (!in_array($logic, $this->supportedLogic, TRUE)) {
				// @LOW _this doesn't really clarify anything if I don't know which piece of configuration is the cause..
				throw new Exception\UnsupportedFeatureType(1448552681, array(
					'LOGICAL OPERATOR', $logic, join('/', $this->supportedLogic)
				));
			}
			$parts = array();
			// a collection requires recursive use of this method
			foreach ($constraint as $subConstraint) {
				$parts[] = $this->transformConstraint($subConstraint);
			}
			return '(' . join(' ' . $logic . ' ', $parts) . ')';
		}

		// @LOW should these be validated? as well as foreign field and alias? they're not provided from user-input, but still..
		$localField = $constraint->getLocalField();
		if ( !isset($localField[0]) ) {
			throw new Exception\MissingConfigurationProperty(1448552629, array(
				$queryPart, 'constraint.localField', json_encode($constraint)
			));
		}
		$localAlias = $constraint->getLocalAlias();
		if ( !isset($localAlias[0]) ) {
			throw new Exception\MissingConfigurationProperty(1448552652, array(
				$queryPart, 'constraint.localAlias', json_encode($constraint)
			));
		}

		$string = $this->transformWrap($localAlias . '.' . $localField, $constraint->getWrapLocal(), $constraint->getWrapDivider()) .
			' ' . $this->resolveOperator($constraint->getOperator()) . ' ';

		if ($constraint instanceof ConstraintByValue) {
			$string .= $this->resolveConstraintValue($constraint->getValue());
		}
		if ($constraint instanceof ConstraintByField) {
			$string .= $constraint->getForeignAlias() . '.' . $constraint->getForeignField();
		}
		return $this->transformWrap($string, $constraint->getWrap(), $constraint->getWrapDivider());
	}

	/**
	 * Transform an ORDERBY configuration, note that the actual sorting
	 * happens on $alias.
	 *
	 * - Requires 'priority' element
	 * - Supports 'sort' element to set order
	 * - Requires either a 'field' and 'tableAlias' parameter OR a $fieldSubstitute parameter
	 *
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy $orderBy
	 * @param string $fieldSubstitute
	 * @return string
	 * @throws Exception\MissingConfigurationProperty
	 * @throws Exception\UnsupportedFeatureType
	 */
	protected function transformOrderBy(OrderBy $orderBy, $fieldSubstitute = NULL) {
		// @LOW _of course, this doesn't make sense since we don't get here if it is NULL.. except the outside check needs to be replaced by different logic
		if ($orderBy->getPriority() === NULL) {
			throw new Exception\MissingConfigurationProperty(1448552721, array(
				'ORDERBY', 'priority', json_encode($orderBy)
			));
		}

		$string = NULL;
		if ($fieldSubstitute !== NULL) {
			$string = $fieldSubstitute;
		} else {
			$tableAlias = $orderBy->getTableAlias();
			if (!isset($tableAlias[0])) {
				throw new Exception\MissingConfigurationProperty(1449052643, array(
					'ORDERBY', 'tableAlias', json_encode($orderBy)
				));
			}
			$field = $orderBy->getField();
			if (!isset($field[0])) {
				throw new Exception\MissingConfigurationProperty(1449052657, array(
					'ORDERBY', 'field', json_encode($orderBy)
				));
			}
			$string = $tableAlias . '.' . $field;
		}

		if ($orderBy->getForceNumeric()) {
			// @LOW _consider supporting a wrap instead, if we're going to have more types of sorting adjustments
				// cause otherwise this feels like doing DATE_FORMAT with a boolean "isDate" :/
			$string = 'CAST(' . $string . ' AS SIGNED)';
		}

		$sortOrder = $orderBy->getSortOrder();
		if ($sortOrder !== NULL) {
			if (!in_array($sortOrder, $this->supportedSorting, TRUE)) {
				throw new Exception\UnsupportedFeatureType(1448552741, array(
					'SORTING ORDER', $sortOrder, join('/', $this->supportedSorting)
				));
			}
			$string .= ' ' . $sortOrder;
		}
		return $string;
	}

	/**
	 * Transforms a wrap
	 *
	 * @param string $string
	 * @param array $wrapArray
	 * @return string
	 */
	protected function transformWrap($string, array $wrapArray, $divider = '|') {
		foreach ($wrapArray as $wrap) {
			// @LOW _doesn't look like a wrap to me, does it? should we rename the feature?
			$string = str_replace($divider, $string, $wrap);
		}
		return $string;
	}

	/**
	 * Resolves an operator for queryConfiguration.
	 *
	 * @param string $operator
	 * @return string
	 * @throws Exception\CannotResolveOperator
	 * @throws Exception\UnsupportedFeatureType
	 */
	protected function resolveOperator($operator) {
		if ( !isset($operator[0]) ) {
			throw new Exception\CannotResolveOperator(1448552699);
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
	 * Resolves a constraint value. If the value is not already a parameter
	 * or a special value, it will be replaced by a parameter, while the actual
	 * value will be set in the $addParameters array.
	 *
	 * @param string $value
	 * @return string
	 * @throws Exception\CannotResolveConstraintValue
	 */
	protected function resolveConstraintValue($value) {
		if ($value === NULL) {
			throw new Exception\CannotResolveConstraintValue(1448552781);
		}

		// if not already a parameter
		if ($value[0] !== ':') {
			$specialValue = strtoupper($value);
			if (in_array($specialValue, $this->specialValues, TRUE)) {
				// special values can be function-names or e.g. NULL
				return $specialValue;
			}
			// not a special value or a parameter, means it needs to converted to one
			$parameterKey = ':AutoConstraintParameter' . $this->parameterCount++;
			$this->addParameters[$parameterKey] = $value;
			return $parameterKey;
		}

		return $value;
	}
}
