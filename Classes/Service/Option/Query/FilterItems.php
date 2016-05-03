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
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryField;
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
use Innologi\Decosdata\Service\Option\QueryOptionService;
/**
 * FilterItems option
 *
 * Filters Items based on the configuration given.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FilterItems extends FilterOptionAbstract {
	// @TODO ___can we include the quick filters?

	/**
	 * Filter is applied on current field configuration.
	 *
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryField()
	 */
	public function alterQueryField(array $args, QueryField $queryField, QueryOptionService $service) {
		$this->initialize($args);
		$id = $queryField->getId() . 'filteritems' . $service->getOptionIndex();

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

		$queryField->getWhere()->addConstraint(
			$id,
			$this->processConditions($args, $conditions)
		);
	}
	// @TODO ___cleanup or finish
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
	public function alterQueryRow(array $args, Query $query, QueryOptionService $service) {
		$this->initialize($args);
		$id = 'filteritems';
		$table = 'tx_decosdata_domain_model_itemfield';

		// note that by $id, we'll always use the same field, this way
		// we can add multiple conditions on the same FROM join if a
		// field is checked on multiple values
		$queryField = $query->getContent($id)->getField('');
		$conditions = array();
		foreach ($args['filters'] as $filter) {
			$this->initializeFilter($filter, TRUE);
			$alias = $id . $filter['field'];
			// identify the join by the field, so we don't create redundant joins
			$from = $queryField->getFrom($filter['field'], array($alias => $table));
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

		$queryField->getWhere()->addConstraint(
			$service->getOptionIndex(),
			$this->processConditions($args, $conditions)
		);
	}

}
