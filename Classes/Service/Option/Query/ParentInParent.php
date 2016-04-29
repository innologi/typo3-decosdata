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
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent;
/**
 * ParentInParent option
 *
 * Looks for a parent of the original item, which is also a child of the
 * original item's main parent, hence parent-in-parent. This is a typical
 * DECOS construct when there is a FOLDER in FOLDER, and the original item
 * is DOCUMENT in both. (a solution to achieve "zaakgericht werken")
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ParentInParent extends OptionAbstract {

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
	 * @inject
	 */
	protected $constraintFactory;

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryColumn()
	 */
	public function alterQueryColumn(array $args, QueryContent $queryContent, $optionIndex) {
		$index = str_replace('content', '', $queryContent->getId());
		$alias = 'relation' . $index;
		$queryField = $queryContent->getParent()->getContent($alias)->getField('itemRelation');

		// relation to original item
		$alias1 = $alias . 'mm1';
		// relation to (shared) main parent, which needs to be added as an OUTER (LEFT) JOIN,
		// since not every item will have a parent-in-parent
		$alias2 = $alias . 'mm2';
		// if we don't pair these up in a single join, the first table will identify
		// with it.uid and therefore with restrictBy.uid_local, which shuts out our
		// ability to get the desired relation with the second table
		$from = $queryField->getFrom('ParentInParent', array(
			$alias1 => 'tx_decosdata_item_item_mm',
			$alias2 => 'tx_decosdata_item_item_mm'
		))->setJoinType('LEFT')->setConstraint(
			$this->constraintFactory->createConstraintAnd(array(
				$this->constraintFactory->createConstraintByField('uid_local', $alias1, '=', 'uid', 'it'),
				$this->constraintFactory->createConstraintByField('uid_local', $alias2, '=', 'uid_foreign', $alias1),
				// @FIX _______what if restrictBy doesn't exist? we can't detect that here if restrictBy is to be added on row-level
				$this->constraintFactory->createConstraintByField('uid_foreign', $alias2, '=', 'uid_foreign', 'restrictBy')
			))
		);

		// set itemtype limitation if any
		// @LOW ___what if not an array or empty?
		if (!isset($args['itemType'])) {
			// we don't need to an additional item-table in the join if there is no itemType to check
			$queryField->getSelect()
				->setField('uid_foreign')
				->setTableAlias($alias1);
		} else {
			// parent in parent item
			$alias3 = $alias . 'item';
			$parameterKey = ':' . $alias3 . 'Type';
			$from->addTable('tx_decosdata_domain_model_item', $alias3)
				->addConstraint(
					'item',
					$this->constraintFactory->createConstraintByField('uid', $alias3, '=', 'uid_foreign', $alias1)
				)->addConstraint(
					'itemtype',
					$this->constraintFactory->createConstraintByValue('item_type', $alias3, 'IN', $parameterKey)
				);
			$queryContent->addParameter($parameterKey, $args['itemType']);

			$queryField->getSelect()
				->setField('uid')
				->setTableAlias($alias3);
		}

		// @TODO ___add import restriction? Try it out without, first, see if it makes a difference.
		// Consider that a parent-in-parent will always need to have been exported in the a single XML
		// with both the main parent and the original item. So the import-restriction might be redundant?


			// prep xml_id limitation
			/*$xmlIdArray = $this->xmlIdArray;
			if (!empty($xmlIdArray)) {
				$xmlTable = ',tx_decospublisher_item_itemxml_mm itxr5s1';
				$xmlCond = '
					AND it5.uid=itxr5s1.uid_local
					AND itxr5s1.uid_foreign IN(\'' . implode('\',\'', $xmlIdArray) . '\')';
			}*/



	}

}
