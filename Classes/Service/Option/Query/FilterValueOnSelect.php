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
 * FilterValueOnSelect option
 *
 * Filters values based on the configuration given, but only in the SELECT.
 * This gives different results compared to FilterValue,
 * when combined with IS NULL checks from FilterItems.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FilterValueOnSelect extends OptionAbstract {
	use Traits\Filters;
	// @TODO add proper IF support at some point, because this is a bit of an unsecure mess
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
		$property = $select->getTableAlias() . '.' . $select->getField();
		$conditions = [];
		foreach ($args['filters'] as $filter) {
			$this->initializeFilter($filter);
			if (isset($filter['parameter'])) {
				// @TODO throw exception or add support
			}
			$conditions[] = $property . ' ' . $filter['operator'] . ' \'' . $filter['value'] . '\'';
		}

		if (!empty($conditions)) {
			$glue = isset($args['matchAll']) && (bool) $args['matchAll'] ? ' AND ' : ' OR ';
			$select->addWrap('filter', 'IF(' . join($glue, $conditions) . ', |, NULL)');
		}
	}

}
