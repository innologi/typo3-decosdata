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
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * Filter Option Abstract
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class FilterOptionAbstract extends OptionAbstract {
	// @TODO ___base FilterItemContent (items outer join), FilterItemsByRelations (relations inner join) on these
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
		if (!isset($args['filters']) || empty($args['filters'])) {
			throw new MissingArgument(1448551220, array(self::class, 'filters'));
		}
	}

	/**
	 * Initializes filter
	 *
	 * @param array &$filter
	 * @param boolean $requireField
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Option\Exception\MissingArgument
	 */
	protected function initializeFilter(array &$filter, $requireField = FALSE) {
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
		if ($requireField && !isset($filter['field'][0]) ) {
			throw new MissingArgument(1448898010, array(self::class, 'filters.field'));
		}
	}

	/**
	 * Determines and returns the Constraint object to be added to
	 * your constraint-container of choice.
	 *
	 * @param array $args
	 * @param array $conditions
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface
	 */
	protected function processConditions(array $args, array $conditions) {
		if (count($conditions) > 1) {
			$logic = isset($args['matchAll']) && (bool) $args['matchAll'] ? 'AND' : 'OR';
			return $this->constraintFactory->createConstraintCollection($logic, $conditions);
		} else {
			return $conditions[0];
		}
	}

}
