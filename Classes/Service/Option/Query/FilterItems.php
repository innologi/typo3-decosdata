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
class FilterItems extends OptionAbstract {
	use Traits\Filters;
	// @TODO ___can we include the quick filters?

	/**
	 * Filter is applied on current field configuration.
	 *
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryField()
	 */
	public function alterQueryField(array $args, QueryField $queryField, QueryOptionService $service) {
		$this->doFiltersExist($args);
		$id = $queryField->getId() . 'filteritems' . $service->getOptionIndex();

		$select = $queryField->getSelect();
		$conditions = [];
		foreach ($args['filters'] as $filter) {
			$this->initializeFilter($filter);
			$conditions[] = $this->constraintFactory->createConstraintByValue(
				$select->getField(),
				$select->getTableAlias(),
				$filter['operator'],
				$filter['value'] ?? $filter['parameter']
			);
		}

		$queryField->getWhere()->addConstraint(
			$id,
			$this->processConditions($args, $conditions)
		);
	}

	/**
	 * Filter can be applied on any field.
	 *
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryRow()
	 */
	public function alterQueryRow(array $args, Query $query, QueryOptionService $service) {
		$this->doFiltersExist($args);
		$id = 'filteritems';

		// note that by $id, we'll always use the same field, this way
		// we can add multiple conditions on the same FROM join if a
		// field is checked on multiple values
		$queryField = $query->getContent($id)->getField('');
		$conditions = [];
		foreach ($args['filters'] as $filter) {
			$this->initializeFilter($filter, TRUE);
			// identify by the field, so we don't create redundant joins
			$alias = $id . $filter['field'];
			$conditions[] = $this->filterBy($queryField, $filter, $alias, $filter['field']);
		}

		$queryField->getWhere()->addConstraint(
			$service->getOptionIndex(),
			$this->processConditions($args, $conditions)
		);
	}

}
