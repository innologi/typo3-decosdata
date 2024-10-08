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

/**
 * Constraint By Field
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ConstraintByField extends ConstraintAbstract
{
    /**
     * @var string
     */
    protected $foreignField;

    /**
     * @var string
     */
    protected $foreignAlias;

    /**
     * @param string $localField
     * @param string $localAlias
     * @param string $operator
     * @param string $foreignField
     * @param string $foreignAlias
     * @return $this
     */
    public function __construct($localField, $localAlias, $operator, $foreignField, $foreignAlias)
    {
        $this->localField = $localField;
        $this->localAlias = $localAlias;
        $this->operator = $operator;
        $this->foreignField = $foreignField;
        $this->foreignAlias = $foreignAlias;
        return $this;
    }

    /**
     * Returns Foreign Field
     *
     * @return string
     */
    public function getForeignField()
    {
        return $this->foreignField;
    }

    /**
     * Sets Foreign Field
     *
     * @param string $foreignField
     * @return $this
     */
    public function setForeignField($foreignField)
    {
        $this->foreignField = $foreignField;
        return $this;
    }

    /**
     * Returns Foreign Field
     *
     * @return string
     */
    public function getForeignAlias()
    {
        return $this->foreignAlias;
    }

    /**
     * Sets Foreign Field
     *
     * @param string $foreignAlias
     * @return $this
     */
    public function setForeignAlias($foreignAlias)
    {
        $this->foreignAlias = $foreignAlias;
        return $this;
    }
}
