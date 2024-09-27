<?php

namespace Innologi\Decosdata\Service\Option\Query;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\QueryOptionService;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory;
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;

/**
 * Add Fields option
 *
 * Adds requested fields to the query.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AddFields extends OptionAbstract
{
    /**
     * @var ConstraintFactory
     */
    protected $constraintFactory;

    public function injectConstraintFactory(ConstraintFactory $constraintFactory)
    {
        $this->constraintFactory = $constraintFactory;
    }

    /**
     * Add (any) item field
     *
     * {@inheritDoc}
     * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryRow()
     */
    public function alterQueryRow(array $args, Query $query, QueryOptionService $service)
    {
        if (!(isset($args['fields']) && is_array($args['fields']))) {
            // @TODO throw exception
        }

        foreach ($args['fields'] as $field) {
            if (!isset($field[0])) {
                // @TODO throw exception
            }
            $tableAlias1 = 'field' . $field;
            $tableAlias2 = $tableAlias1 . 'f';
            $parameterKey = ':' . $tableAlias1;

            // @LOW should check if a field already exists
            // @LOW should we check if it.uid can be linked? What happens if this option is used on a query grouped by content?
            $queryField = $query->getContent($field)->getField('');
            $queryField->getSelect()
                ->setField('field_value')
                ->setTableAlias($tableAlias1);

            $queryField->getFrom(0, [
                $tableAlias1 => 'tx_decosdata_domain_model_itemfield',
                $tableAlias2 => 'tx_decosdata_domain_model_field',
            ])->setJoinType('LEFT')
            ->setConstraint(
                $this->constraintFactory->createConstraintAnd([
                    $this->constraintFactory->createConstraintByField('item', $tableAlias1, '=', 'uid', 'it'),
                    $this->constraintFactory->createConstraintByField('field', $tableAlias1, '=', 'uid', $tableAlias2),
                    $this->constraintFactory->createConstraintByValue('field_name', $tableAlias2, '=', $parameterKey),
                ]),
            );

            $query->addParameter($parameterKey, $field);
        }
    }
}
