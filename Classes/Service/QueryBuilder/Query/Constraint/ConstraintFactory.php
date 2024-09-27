<?php

namespace Innologi\Decosdata\Service\QueryBuilder\Query\Constraint;

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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Constraint Container
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ConstraintFactory implements SingletonInterface
{
    /**
     * Creates ConstraintCollection object and returns it.
     *
     * @param string $logic
     * @return ConstraintCollection
     */
    public function createConstraintCollection($logic, array $constraints = [])
    {
        return match ($logic) {
            'AND' => $this->createConstraintAnd($constraints),
            'OR' => $this->createConstraintOr($constraints),
            default => GeneralUtility::makeInstance(
                ConstraintCollection::class,
                $logic,
                $constraints,
            ),
        };
    }

    /**
     * Creates ConstraintAnd Collection object and returns it.
     *
     * @return ConstraintAnd
     */
    public function createConstraintAnd(array $constraints = [])
    {
        return GeneralUtility::makeInstance(
            ConstraintAnd::class,
            $constraints,
        );
    }

    /**
     * Creates ConstraintOr Collection object and returns it.
     *
     * @return ConstraintOr
     */
    public function createConstraintOr(array $constraints = [])
    {
        return GeneralUtility::makeInstance(
            ConstraintOr::class,
            $constraints,
        );
    }

    /**
     * Creates ConstraintByField object and returns it.
     *
     * @param string $localField
     * @param string $localAlias
     * @param string $operator
     * @param string $foreignField
     * @param string $foreignAlias
     * @return ConstraintByField
     */
    public function createConstraintByField($localField, $localAlias, $operator, $foreignField, $foreignAlias)
    {
        return GeneralUtility::makeInstance(
            ConstraintByField::class,
            $localField,
            $localAlias,
            $operator,
            $foreignField,
            $foreignAlias,
        );
    }

    /**
     * Creates ConstraintByValue object and returns it.
     *
     * @param string $field
     * @param string $alias
     * @param string $operator
     * @param mixed $value
     * @return ConstraintByValue
     */
    public function createConstraintByValue($field, $alias, $operator, $value)
    {
        return GeneralUtility::makeInstance(
            ConstraintByValue::class,
            $field,
            $alias,
            $operator,
            $value,
        );
    }
}
