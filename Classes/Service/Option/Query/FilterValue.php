<?php
namespace Innologi\Decosdata\Service\Option\Query;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\QueryOptionService;
/**
 * FilterValue option
 *
 * Filters values based on the configuration given.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FilterValue extends OptionAbstract {
	use Traits\Filters;

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
			$conditions[] = isset($filter['value']) ?
				$this->constraintFactory->createConstraintByValue(
					$select->getField(),
					$select->getTableAlias(),
					$filter['operator'],
					$filter['value']
				) : $this->constraintFactory->createConstraintByValue(
					'uid',
					$select->getTableAlias(),
					$filter['operator'],
					$filter['parameter']
				);
		}

		$queryField->getFrom(0)->addConstraint(
			$id,
			$this->processConditions($args, $conditions)
		);
	}

}
