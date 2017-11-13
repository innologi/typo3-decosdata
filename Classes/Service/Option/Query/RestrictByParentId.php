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
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
use Innologi\Decosdata\Service\Option\QueryOptionService;
/**
 * RestrictByParentId option
 *
 * Restricts shown items by a parent item id
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RestrictByParentId extends OptionAbstract {
	use Traits\Filters;

	/**
	 * Restricts items by parent item id
	 *
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryRow()
	 */
	public function alterQueryRow(array $args, Query $query, QueryOptionService $service) {
		$itemId = $this->getParameterFilterValue($args);

		$alias = 'restrictBy';
		$parameterKey = ':' . $alias;
		$query->getContent('itemID')->getField('')
			->getFrom($alias, array($alias => 'tx_decosdata_item_item_mm'))
			->setJoinType('INNER')->setConstraint(
				$this->constraintFactory->createConstraintAnd(array(
					'relation' => $this->constraintFactory->createConstraintByField('uid_local', $alias, '=', 'uid', 'it'),
					'restriction' => $this->constraintFactory->createConstraintByValue('uid_foreign', $alias, '=', $parameterKey)
				))
			);
		$query->addParameter($parameterKey, $itemId);
	}

}
