<?php
namespace Innologi\Decosdata\Service\Option\Query;
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
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryField;
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * FilterItems option
 *
 * Filters Items based on the configuration given.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FilterItems extends OptionAbstract {
	// @TODO ___can we include the quick filters?
	// @TODO ___base FilterSubItem(?) on this one, see if we can create an abstract on which both rely

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
	 * @inject
	 */
	protected $constraintFactory;

	/**
	 * Initializes public methods by providing shared logic
	 *
	 * @param array $args
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Option\Exception\MissingArgument
	 */
	protected function initialize(array $args) {
		if (!isset($args['filters'][0])) {
			// @TODO ___test this
			throw new MissingArgument(1448551220, array(self::class, 'filters'));
		}
	}

	/**
	 * Initializes filter
	 *
	 * @param array &$filter
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Option\Exception\MissingArgument
	 */
	protected function initializeFilter(array &$filter) {
		if (!isset($filter['operator'][0])) {
			throw new MissingArgument(1448897878, array(self::class, 'filters.operator'));
		}
		if (!isset($filter['value'])) {
			if (!isset($filter['parameter'][0])) {
				throw new MissingArgument(1448897891, array(self::class, 'filters.value/parameter'));
			}
			// @LOW ___add support for other extension parameters?
			// @TODO ___not validated. shouldn't we get it from controller/request or something? that way we can keep validation on a single location
			$param = GeneralUtility::_GP('tx_decosdata_publish');
			$filter['value'] = rawurldecode($param[$filter['parameter']]);
			// @TODO ___throw exception if it does not exist?
		}
	}

	/**
	 * Filter is applied on current field configuration.
	 *
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryField()
	 */
	public function alterQueryField(array $args, QueryField $queryField, $optionIndex) {
		$this->initialize($args);
		$id = $queryField->getId() . 'filteritems' . $optionIndex;

		$select = $queryField->getSelect();
		$conditions = array();
		foreach ($args['filters'] as $filter) {
			$this->initializeFilter($filter);
			$conditions[] = $this->constraintFactory->createConstraintByValue(
				$select->getField(),
				$select->getTableAlias(),
				$filter['operator'],
				$filter['value']
			);
		}

		$constraint = NULL;
		if (count($conditions) > 1) {
			$logic = isset($args['matchAll']) && (bool) $args['matchAll'] ? 'AND' : 'OR';
			$constraint = $this->constraintFactory->createConstraintCollection($logic, $conditions);
		} else {
			$constraint = $conditions[0];
		}

		$queryField->getWhere()->addConstraint($id, $constraint);
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryColumn()
	 */
	/*public function alterQueryColumn(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		$this->initialize($args);

				$fieldId = $filter['contentField'];
				if (!isset($queryConfiguration[$fieldId][0]['SELECT']['alias'])) {

				}
				$queryConfiguration[$fieldId][]['WHERE'][] = array(
					'field' => $queryConfiguration[$filter['contentField']][0]['SELECT']['alias'],
					'operator' => $this->resolveOperator($filter),
					'value' => $this->resolveComparisonValue($filter, 'WHERE')
				);

	}*/

	/**
	 * Filter can be applied on any field.
	 *
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryRow()
	 */
	public function alterQueryRow(array $args, Query $query, $optionIndex) {
		$this->initialize($args);
		$id = 'filteritems';
		$table = 'tx_decosdata_domain_model_itemfield';

		// note that by $id, we'll always use the same field, this way
		// we can add multiple conditions on the same FROM join if a
		// field is checked on multiple values
		$queryField = $query->getContent($id)->getField('');
		$conditions = array();
		foreach ($args['filters'] as $filter) {
			$this->initializeFilter($filter);
			if ( !(isset($filter['field']) && is_int($filter['field'])) ) {
				throw new MissingArgument(1448898010, array(self::class, 'filters.field'));
			}
			$alias = $id . $filter['field'];
			// identify the join by the field, so we don't create redundant joins
			$from = $queryField->getFrom($filter['field'], $table, $alias);
			if ($from->getJoinType() === NULL) {
				// initialize join if it did not exist yet
				$parameterKey = ':' . $alias;
				$from->setJoinType('LEFT')->setConstraint(
					$this->constraintFactory->createConstraintAnd(array(
						'item' => $this->constraintFactory->createConstraintByField('item', $alias, '=', 'uid', 'it'),
						'field' => $this->constraintFactory->createConstraintByValue('field', $alias, '=', $parameterKey)
					))
				);
				$query->addParameter($parameterKey, $filter['field']);
			}

			$conditions[] = $this->constraintFactory->createConstraintByValue(
				'field_value',
				$alias,
				$filter['operator'],
				$filter['value']
			);
		}

		$constraint = NULL;
		if (count($conditions) > 1) {
			$logic = isset($args['matchAll']) && (bool) $args['matchAll'] ? 'AND' : 'OR';
			$constraint = $this->constraintFactory->createConstraintCollection($logic, $conditions);
		} else {
			$constraint = $conditions[0];
		}

		$queryField->getWhere()->addConstraint($optionIndex, $constraint);
	}

}
