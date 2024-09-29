<?php

namespace Innologi\Decosdata\Service\Option\Query\Traits;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
use Innologi\Decosdata\Service\ParameterService;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\From;
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryField;

/**
 * Filters Trait
 *
 * Contains methods and properties used by Filter-options.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait Filters
{
    // @TODO ___base FilterItemContent (items outer join), FilterItemsByRelations (relations inner join) on these
    /**
     * @var ParameterService
     */
    protected $parameterService;

    /**
     * @var ConstraintFactory
     */
    protected $constraintFactory;

    public function injectParameterService(ParameterService $parameterService): void
    {
        $this->parameterService = $parameterService;
    }

    public function injectConstraintFactory(ConstraintFactory $constraintFactory): void
    {
        $this->constraintFactory = $constraintFactory;
    }

    /**
     * Initializes public methods by providing shared logic
     *
     * @throws \Innologi\Decosdata\Service\Option\Exception\MissingArgument
     */
    protected function doFiltersExist(array $args)
    {
        if (!isset($args['filters']) || empty($args['filters'])) {
            throw new MissingArgument(1448551220, [self::class, 'filters']);
        }
    }

    /**
     * Initializes filter
     *
     * @param boolean $requireField
     * @throws \Innologi\Decosdata\Service\Option\Exception\MissingArgument
     */
    protected function initializeFilter(array &$filter, $requireField = false)
    {
        if (!isset($filter['operator'][0])) {
            throw new MissingArgument(1448897878, [self::class, 'filters.operator']);
        }
        if (!isset($filter['value'])) {
            if (!isset($filter['parameter'][0])) {
                throw new MissingArgument(1448897891, [self::class, 'filters.value/parameter']);
            }
            $filter['parameter'] = $this->parameterService->getMultiParameter($filter['parameter']);
        }
        if ($requireField && !isset($filter['field'][0])) {
            throw new MissingArgument(1448898010, [self::class, 'filters.field']);
        }
    }

    /**
     * Filter by either Value or Parameter
     *
     * @param string $alias
     * @param string $joinId
     * @param string $bindAlias
     * @param string $bindField
     * @throws MissingArgument
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface
     */
    protected function filterBy(QueryField $queryField, array $filter, $alias, $joinId, $bindAlias = 'it', $bindField = 'uid')
    {
        if (isset($filter['value'])) {
            return $this->filterByValue($queryField, $filter, $alias, $joinId, $bindAlias, $bindField);
        }
        if (isset($filter['parameter'])) {
            return $this->filterByParameter($queryField, $filter, $alias, $joinId, $bindAlias, $bindField);
        }
        throw new MissingArgument(1510657518, [self::class, 'filters.value/parameter']);
    }

    /**
     * Sets up a filter by value, taking care of any missing joins in the process.
     *
     * @param string $alias
     * @param string $joinId
     * @param string $bindAlias
     * @param string $bindField
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintByValue
     */
    protected function filterByValue(QueryField $queryField, array $filter, $alias, $joinId, $bindAlias = 'it', $bindField = 'uid')
    {
        $from = $queryField->getFrom($joinId);
        // note that we do LEFT and not INNER joins so the WHERE conditions can be used to filter on IS NULL as well
        if ($from->getJoinType() === null) {
            $from->setJoinType('LEFT');
        }
        // initialize join if it did not exist yet
        if ($from->getTableNameByAlias($alias) === null) {
            $parameterKey = ':' . $alias;
            $this->addFilterJoin($from, $alias, $parameterKey, $bindAlias, $bindField);
            $queryField->addParameter($parameterKey, $filter['field']);
        }

        return $this->constraintFactory->createConstraintByValue(
            'field_value',
            $alias,
            $filter['operator'],
            $filter['value'],
        );
    }

    /**
     * Sets up a filter by parameter, taking care of any missing joins in the process.
     *
     * @param string $alias
     * @param string $joinId
     * @param string $bindAlias
     * @param string $bindField
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintByField
     */
    protected function filterByParameter(QueryField $queryField, array $filter, $alias, $joinId, $bindAlias = 'it', $bindField = 'uid')
    {
        $aliasITF = $alias . 'itf';
        $from = $queryField->getFrom($joinId);
        // note that we do LEFT and not INNER joins so the WHERE conditions can be used to filter on IS NULL as well
        if ($from->getJoinType() === null) {
            $from->setJoinType('LEFT');
        }
        // initialize join if it did not exist yet
        if ($from->getTableNameByAlias($alias) === null) {
            $parameterKey1 = ':' . $alias;
            $parameterKey2 = ':' . $aliasITF;
            $this->addFilterJoin($from, $alias, $parameterKey1, $bindAlias, $bindField);
            $from->addTable('tx_decosdata_domain_model_itemfield', $aliasITF)
                ->addConstraint(
                    $aliasITF,
                    $this->constraintFactory->createConstraintByValue('uid', $aliasITF, '=', $parameterKey2),
                );
            $queryField->addParameter($parameterKey1, $filter['field']);
            $queryField->addParameter($parameterKey2, $filter['parameter']);
        }

        return $this->constraintFactory->createConstraintByField(
            'field_value',
            $alias,
            $filter['operator'],
            'field_value',
            $aliasITF,
        );
    }

    /**
     * Adds filter joins to From object
     *
     * @param string $alias
     * @param string $parameterKey
     * @param string $bindAlias
     * @param string $bindField
     */
    protected function addFilterJoin(From $from, $alias, $parameterKey, $bindAlias, $bindField)
    {
        $aliasF = $alias . 'f';
        $from->addTable('tx_decosdata_domain_model_itemfield', $alias)
            ->addTable('tx_decosdata_domain_model_field', $aliasF)
            ->addConstraint(
                $alias,
                $this->constraintFactory->createConstraintAnd([
                    'item' => $this->constraintFactory->createConstraintByField('item', $alias, '=', $bindField, $bindAlias),
                    'field' => $this->constraintFactory->createConstraintByField('field', $alias, '=', 'uid', $aliasF),
                    'fieldName' => $this->constraintFactory->createConstraintByValue('field_name', $aliasF, '=', $parameterKey),
                ]),
            );
    }

    /**
     * Determines and returns the Constraint object to be added to
     * your constraint-container of choice.
     *
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface
     */
    protected function processConditions(array $args, array $conditions)
    {
        if (count($conditions) > 1) {
            $logic = isset($args['matchAll']) && (bool) $args['matchAll'] ? 'AND' : 'OR';
            return $this->constraintFactory->createConstraintCollection($logic, $conditions);
        }
        return $conditions[0];
    }
}
