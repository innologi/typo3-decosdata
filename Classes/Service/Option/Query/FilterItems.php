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
		if (!isset($args['filters'])) {
			// @TODO ___test this
			throw new MissingArgument(1448551220, array(self::class, 'filters'));
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
			// @TODO ___how do we determine if this one is safe or unsafe? In QueryConfigurator? By property? HOWWW??
			$conditions[] = $this->constraintFactory->createConstraintByValue(
				$select->getField(),
				$select->getTableAlias(),
				$filter['operator'],
				$filter['value']
			);
		}

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
	}*/

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryRow()
	 */
	/*public function alterQueryRow(array $args, Query $configuration, $optionIndex) {
		$this->initialize($args);

		$counter = 0;
		$addedFields = array();
		$constraintType = isset($args['matchAll']) && (bool) $args['matchAll'] ? 'AND' : 'OR';
		foreach ($args['filters'] as $filter) {
			if (isset($filter['contentField'])) {
				$fieldId = $filter['contentField'];
				if (!isset($queryConfiguration[$fieldId][0]['SELECT']['alias'])) {
					// @TODO _throw exception
				}
				$queryConfiguration[$fieldId][]['WHERE'][] = array(
					'field' => $queryConfiguration[$filter['contentField']][0]['SELECT']['alias'],
					'operator' => $this->resolveOperator($filter),
					'value' => $this->resolveComparisonValue($filter, 'WHERE')
				);

			} elseif (isset($filter['field'])) {
				// @FIX _this doesn't work if the above matchAll is FALSE, think this through
				// this order is important, because $this->resolveComparisonValue() will add a second parameter
				$queryConfiguration->addParameter(':filter-items-row', $filter['field']);
				$queryConfiguration->addQueryFrom(
					$this->queryConfigurator->provideFrom(
						'tx_decosdata_domain_model_itemfield', 'filter'.$counter, 'INNER',
						array(
							'item/=/it/uid',
							'field/=/:filter-items-row',
							'field_value/'.$filter['operator'].'/'.$filter['value']
						)
					)
				);

				#array(
				#	'localField' => 'field_value',
				#	'operator' => $this->resolveOperator($filter),
				#	'value' => $this->resolveComparisonValue($filter, 'FROM')
				#)

			} else {
				// @TODO _throw exception
			}
		}
	}*/

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryRow()
	 */
	/*public function alterQueryRow(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		$this->initialize($args);
		$counter = 0;
		$addedFields = array();
		$constraintType = isset($args['matchAll']) && (bool) $args['matchAll'] ? 'AND' : 'OR';
		foreach ($args['filters'] as $filter) {
			if (isset($filter['contentField'])) {
				$fieldId = $filter['contentField'];
				if (!isset($queryConfiguration[$fieldId][0]['SELECT']['alias'])) {
				}
				$queryConfiguration[$fieldId][]['WHERE'][] = array(
					'field' => $queryConfiguration[$filter['contentField']][0]['SELECT']['alias'],
					'operator' => $this->resolveOperator($filter),
					'value' => $this->resolveComparisonValue($filter, 'WHERE')
				);

			} elseif (isset($filter['field'])) {
				// this order is important, because $this->resolveComparisonValue() will add a second parameter
				$this->parameterAdd['FROM'][] = $filter['field'];
				$this->queryAdd['FROM'][] = array(
					'joinType' => 'INNER',
					'table' => 'tx_decosdata_domain_model_itemfield',
					'alias' => 'filter' . $counter,
					'constraints' => array(
						array(
							'localField' => 'item',
							'operator' => '=',
							'foreignAlias' => 'it',
							'foreignField' => 'uid'
						),
						array(
							'localField' => 'field',
							'operator' => '=',
							'value' => '?'
						),
						array(
							'localField' => 'field_value',
							'operator' => $this->resolveOperator($filter),
							'value' => $this->resolveComparisonValue($filter, 'FROM')
						)
					),
					'matchAll' => TRUE
				);

			} else {
			}

		}
	}*/

}
