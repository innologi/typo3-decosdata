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
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintByField;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintByValue;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintCollection;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\From;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\Select;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\Where;
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Query Configurator
 *
 * Provides methods to transform Query objects to Query Parts.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryConfigurator implements SingletonInterface
{
    // @LOW _lots of hardcoded SQL keywords that should be provided by query provider class from database service
    /**
     * @var array
     */
    protected $supportedSorting = ['ASC', 'DESC'];

    /**
     * @var array
     */
    protected $supportedJoins = ['LEFT', 'RIGHT', 'INNER'];

    /**
     * @var array
     */
    protected $supportedLogic = ['AND', 'OR'];

    /**
     * @var array
     */
    protected $specialValues = ['NULL', 'NOW()'];

    /**
     * @var array
     */
    protected $supportedOperators = ['=', '<', '>', '<=', '>=', '<>', '!=', '<=>', 'IS', 'NOT', 'REGEXP', 'RLIKE', 'LIKE', 'IN'];

    // This class becomes more and more dirty, but at least it works nicely until we're in need of a cleanup and can revise our query building mechanism to more modern standards
    /**
     * @var array
     */
    protected $defaultConstraints = [
        'tx_decosdata_domain_model_field' => ['deleted'],
        'tx_decosdata_domain_model_itemtype' => ['deleted'],
        // @TODO note that not all queries are joining this table, but only use the relation table.. might want to fix that
        'tx_decosdata_domain_model_import' => ['deleted', 'hidden'],
        'tx_decosdata_domain_model_item' => ['deleted', 'hidden'],
        'tx_decosdata_domain_model_itemblob' => ['deleted', 'hidden'],
        'tx_decosdata_domain_model_itemfield' => ['deleted', 'hidden'],
        'tx_decosdata_domain_model_profile' => ['deleted', 'hidden'],
        'tx_decosdata_domain_model_profilefield' => ['deleted', 'hidden'],
    ];

    /**
     * @var array
     */
    protected $addParameters = [];

    /**
     * @var integer
     */
    protected $parameterCount = 0;

    /**
     * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
     */
    protected $constraintFactory;

    /**
     * Returns ConstraintFactory.
     *
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
     */
    protected function getConstraintFactory()
    {
        if ($this->constraintFactory === null) {
            $this->constraintFactory = GeneralUtility::makeInstance(ConstraintFactory::class);
        }
        return $this->constraintFactory;
    }

    /**
     * Transforms a Query object to actual SQL Query Parts.
     *
     * @return array
     */
    public function transformConfiguration(Query $query)
    {
        $queryParts = [];
        $glue = [
            'SELECT' => ',',
            'FROM' => PHP_EOL,
            'WHERE' => ' AND ',
            'GROUPBY' => ',',
            'ORDERBY' => ',',
        ];
        $this->addParameters = [];

        // Query consists of QueryContent with an ID that represents content alias.
        // This object-approach effectively gives us control to alter specific configuration parts from
        // other parts (e.g. options), and to impose a specific order of parts before I'm left to fiddle
        // with hacky string manipulation as was the case in tx_decospublisher.

        /** @var \Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent $queryContent */
        foreach ($query as $id => $queryContent) {
            $concatSelect = [];
            /** @var \Innologi\Decosdata\Service\QueryBuilder\Query\QueryField $queryField */
            foreach ($queryContent as $queryField) {
                // @LOW ___this doesn't look right, I have dedicated methods which should isolate everything pertaining to the object, but I still have to check specific values before executing them
                $select = $queryField->getSelect();
                if ($select->getField() !== null) {
                    $concatSelect[] = $this->transformSelect($select);
                }
                $fromArray = $queryField->getFromAll();
                $where = $queryField->getWhere();
                foreach ($fromArray as $from) {
                    $queryParts['FROM'][] = $this->transformFrom($from, $where);
                }
                if ($where->getConstraint() !== null) {
                    $queryParts['WHERE'][] = $this->transformConstraint($where->getConstraint(), 'WHERE');
                }
                $orderBy = $queryField->getOrderBy();
                if ($orderBy->getPriority() !== null) {
                    $queryParts['ORDERBY'][$orderBy->getPriority()] = $this->transformOrderBy($orderBy);
                }
            }

            // applies concatting SELECT per id, to form a single alias per content field
            if (!empty($concatSelect)) {
                $queryParts['SELECT'][] = $this->concatSelect($concatSelect, $id, $queryContent->getFieldSeparator());
            }

            $groupBy = $queryContent->getGroupBy();
            if ($groupBy->getPriority() !== null) {
                $queryParts['GROUPBY'][$groupBy->getPriority()] = $id;
            }
            $orderBy = $queryContent->getOrderBy();
            if ($orderBy->getPriority() !== null) {
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
            $queryParts['ORDERBY'] = ['NULL'];
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
     * @return string
     * @throws Exception\MissingConfigurationProperty
     */
    protected function transformSelect(Select $select)
    {
        $field = $select->getField();
        if (!isset($field[0])) {
            throw new Exception\MissingConfigurationProperty(1448552576, [
                'SELECT', 'field', json_encode($select),
            ]);
        }

        $tableAlias = $select->getTableAlias();
        if (!isset($tableAlias[0])) {
            throw new Exception\MissingConfigurationProperty(1448622360, [
                'SELECT', 'tableAlias', json_encode($select),
            ]);
        }

        return $this->transformWrap($tableAlias . '.' . $field, $select->getWrap(), $select->getWrapDivider());
    }
    // @TODO shouldn't the separator go through parameters instead?
    /**
     * Joins multiple SELECT fields under a single alias
     * through concat mysql function(s).
     *
     * @param string $alias
     * @param string $select separator
     * @return string
     */
    protected function concatSelect(array $select, $alias, $separator = ' ')
    {
        return (count($select) > 1
            ? 'CONCAT_WS(\'' . $separator . '\',' . join(',', $select) . ')'
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
     * @return string
     * @throws Exception\MissingConfigurationProperty
     * @throws Exception\UnsupportedFeatureType
     */
    protected function transformFrom(From $from, Where $where)
    {
        $tables = $from->getTables();
        if (!isset($tables) || empty($tables)) {
            throw new Exception\MissingConfigurationProperty(1448552598, [
                'FROM', 'tables', json_encode($from),
            ]);
        }

        $formatTables = [];
        $joinType = $from->getJoinType();
        foreach ($tables as $alias => $table) {
            $formatTables[] = $table . ' ' . $alias;

            // if the table has known default constraints, add them here unless this was disabled
            if ($from->getDefaultRestrictions() && isset($this->defaultConstraints[$table])) {
                $defaultConstraints = $this->defaultConstraints[$table];
                // if there is no join type, it will not have any constraints, so we'll put them in WHERE instead
                $constraintContainer = $joinType !== null ? $from : $where;
                foreach ($defaultConstraints as $defaultConstraint) {
                    $constraintContainer->addConstraint(
                        '__default__' . $alias . '__' . $defaultConstraint,
                        $this->getConstraintFactory()->createConstraintByValue($defaultConstraint, $alias, '=', 0),
                    );
                }
                // @LOW yet another hack.. this class should not be adding constraints
                // imagine what happens if a query object is amended and passed through it again
                // hence we disable it here once it has been added once
                $from->setDefaultRestrictions(false);
            }
        }
        $string = '(' . join(',', $formatTables) . ')';


        // @LOW _if joinType is NULL, transformConfiguration will still join the table with "\n" and not with a comma..
        if ($joinType !== null) {
            if (!in_array($joinType, $this->supportedJoins, true)) {
                throw new Exception\UnsupportedFeatureType(1448552612, [
                    'TABLE JOIN TYPE', $joinType, join('/', $this->supportedJoins),
                ]);
            }
            $string = $joinType . ' JOIN ' . $string;
            $constraint = $from->getConstraint();
            // constraints are only applied on a join, otherwise they're ignored
            if ($constraint !== null) {
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
     * @param string $queryPart
     * @throws Exception\UnsupportedFeatureType
     * @throws Exception\MissingConfigurationProperty
     * @return string
     */
    protected function transformConstraint(ConstraintInterface $constraint, $queryPart = '')
    {
        if ($constraint instanceof ConstraintCollection) {
            $logic = $constraint->getLogic();
            if (!in_array($logic, $this->supportedLogic, true)) {
                // @LOW _this doesn't really clarify anything if I don't know which piece of configuration is the cause..
                throw new Exception\UnsupportedFeatureType(1448552681, [
                    'LOGICAL OPERATOR', $logic, join('/', $this->supportedLogic),
                ]);
            }
            $parts = [];
            // a collection requires recursive use of this method
            foreach ($constraint as $subConstraint) {
                $parts[] = $this->transformConstraint($subConstraint);
            }
            return '(' . join(' ' . $logic . ' ', $parts) . ')';
        }

        // @LOW should these be validated? as well as foreign field and alias? they're not provided from user-input, but still..
        $localField = $constraint->getLocalField();
        if (!isset($localField[0])) {
            throw new Exception\MissingConfigurationProperty(1448552629, [
                $queryPart, 'constraint.localField', json_encode($constraint),
            ]);
        }
        $localAlias = $constraint->getLocalAlias();
        if (!isset($localAlias[0])) {
            throw new Exception\MissingConfigurationProperty(1448552652, [
                $queryPart, 'constraint.localAlias', json_encode($constraint),
            ]);
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
     * @param string $fieldSubstitute
     * @return string
     * @throws Exception\MissingConfigurationProperty
     * @throws Exception\UnsupportedFeatureType
     */
    protected function transformOrderBy(OrderBy $orderBy, $fieldSubstitute = null)
    {
        // @LOW _of course, this doesn't make sense since we don't get here if it is NULL.. except the outside check needs to be replaced by different logic
        if ($orderBy->getPriority() === null) {
            throw new Exception\MissingConfigurationProperty(1448552721, [
                'ORDERBY', 'priority', json_encode($orderBy),
            ]);
        }

        $string = null;
        if ($fieldSubstitute !== null) {
            $string = $fieldSubstitute;
        } else {
            $tableAlias = $orderBy->getTableAlias();
            if (!isset($tableAlias[0])) {
                throw new Exception\MissingConfigurationProperty(1449052643, [
                    'ORDERBY', 'tableAlias', json_encode($orderBy),
                ]);
            }
            $field = $orderBy->getField();
            if (!isset($field[0])) {
                throw new Exception\MissingConfigurationProperty(1449052657, [
                    'ORDERBY', 'field', json_encode($orderBy),
                ]);
            }
            $string = $tableAlias . '.' . $field;
        }

        if ($orderBy->getForceNumeric()) {
            // @LOW _consider supporting a wrap instead, if we're going to have more types of sorting adjustments
            // cause otherwise this feels like doing DATE_FORMAT with a boolean "isDate" :/
            $string = 'CAST(' . $string . ' AS SIGNED)';
        }

        $sortOrder = $orderBy->getSortOrder();
        if ($sortOrder !== null) {
            if (!in_array($sortOrder, $this->supportedSorting, true)) {
                throw new Exception\UnsupportedFeatureType(1448552741, [
                    'SORTING ORDER', $sortOrder, join('/', $this->supportedSorting),
                ]);
            }
            $string .= ' ' . $sortOrder;
        }
        return $string;
    }

    /**
     * Transforms a wrap
     *
     * @param string $string
     * @return string
     */
    protected function transformWrap($string, array $wrapArray, $divider = '|')
    {
        foreach ($wrapArray as $wrap) {
            // @LOW _doesn't look like a wrap to me, does it? should we rename the feature?
            $string = str_replace($divider, $string, (string) $wrap);
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
    protected function resolveOperator($operator)
    {
        if (!isset($operator[0])) {
            throw new Exception\CannotResolveOperator(1448552699);
        }

        $operators = explode(' ', strtoupper($operator));
        foreach ($operators as $operator) {
            if (!in_array($operator, $this->supportedOperators, true)) {
                throw new Exception\UnsupportedFeatureType(1448552756, [
                    'COMPARISON OPERATOR', $operator, join('/', $this->supportedOperators),
                ]);
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
    protected function resolveConstraintValue($value)
    {
        if ($value === null) {
            throw new Exception\CannotResolveConstraintValue(1448552781);
        }
        $value = (string) $value;

        // if not already a parameter
        if (!\str_starts_with($value, ':')) {
            $specialValue = strtoupper($value);
            if (in_array($specialValue, $this->specialValues, true)) {
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
