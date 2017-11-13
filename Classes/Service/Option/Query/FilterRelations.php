<?php
namespace Innologi\Decosdata\Service\Option\Query;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\Exception\MissingDependency;
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent;
use Innologi\Decosdata\Service\Option\QueryOptionService;
/**
 * FilterRelations option
 *
 * Filters Relations based on the configuration given.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FilterRelations extends OptionAbstract {
	use Traits\Filters;

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryColumn()
	 */
	public function alterQueryColumn(array $args, QueryContent $queryContent, QueryOptionService $service) {
		$this->doFiltersExist($args);
		$id = 'relation' . $service->getIndex();
		$aliasId = $id . 'filter';

		// note that we use the same id as any relation-type option, e.g. ParentInParent,
		// so that we can influence said relation with constraints
		$queryField = $queryContent->getParent()->getContent($id)->getField('');
		$select = $queryField->getSelect();
		$from = $queryField->getFrom('');
		$tables = $from->getTables();
		if (empty($tables)) {
			// if join didn't already exist with any tables, this is a misconfiguration
			throw new MissingDependency(1462201871, [self::class, 'No relation set']);
		}

		$conditions = [];
		foreach ($args['filters'] as $filter) {
			$this->initializeFilter($filter, TRUE);
			// identify the table by the field, so we don't create redundancy
			$alias = $aliasId . $filter['field'];
			if ($from->getTableNameByAlias($alias) === NULL) {
				// if alias wasn't created before, do so now
				$parameterKey = ':' . $alias;
				$tableAlias1 = $alias . 'i';
				$tableAlias2 = $alias . 'f';
				$from->addTable('tx_decosdata_domain_model_itemfield', $tableAlias1)
					->addTable('tx_decosdata_domain_model_field', $tableAlias2)
					->addConstraint(
						$alias,
						$this->constraintFactory->createConstraintAnd([
							'item' => $this->constraintFactory->createConstraintByField('item', $tableAlias1, '=', $select->getField(), $select->getTableAlias()),
							'field' => $this->constraintFactory->createConstraintByField('field', $tableAlias1, '=', 'uid', $tableAlias2),
							'fieldName' => $this->constraintFactory->createConstraintByValue('field_name', $tableAlias2, '=', $parameterKey)
						])
					);
				$queryField->addParameter($parameterKey, $filter['field']);
			}

			$conditions[] = $this->constraintFactory->createConstraintByValue(
				'field_value',
				$tableAlias1,
				$filter['operator'],
				$filter['value']
			);
		}

		$from->addConstraint(
			$aliasId . $service->getOptionIndex(),
			$this->processConditions($args, $conditions)
		);
	}

}
