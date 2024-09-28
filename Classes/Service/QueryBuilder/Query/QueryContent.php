<?php

namespace Innologi\Decosdata\Service\QueryBuilder\Query;

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
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\GroupBy;
use Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Query Content object
 *
 * Contains Query sub-configuration for one content and all its fields.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryContent extends QueryIterator implements QueryInterface
{
    /**
     * @var Query
     */
    protected $parent;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $fieldSeparator = ' ';

    /**
     * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy
     */
    protected $orderBy;

    /**
     * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Part\GroupBy
     */
    protected $groupBy;

    /**
     * @param string $id
     * @return $this
     */
    public function __construct($id, QueryInterface $parent)
    {
        // @extensionScannerIgnoreLine false positive
        $this->id = $id;
        $this->parent = $parent;
        $this->orderBy = GeneralUtility::makeInstance(OrderBy::class);
        $this->groupBy = GeneralUtility::makeInstance(GroupBy::class);
        return $this;
    }

    /**
     * Returns OrderBy object
     *
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\OrderBy
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Returns GroupBy object
     *
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Part\GroupBy
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * Returns Query Field object. If it does not exist yet, it is created.
     *
     * @param string $subId
     * @return QueryField
     */
    public function getField($subId)
    {
        // @extensionScannerIgnoreLine false positive
        $id = $this->id . $subId;
        if (!isset($this->children[$id])) {
            $this->children[$id] = GeneralUtility::makeInstance(QueryField::class, $id, $this);
        }
        return $this->children[$id];
    }

    /**
     * Returns id
     *
     * @return string
     */
    public function getId()
    {
        // @extensionScannerIgnoreLine false positive
        return $this->id;
    }

    /**
     * Returns field separator
     *
     * @return string
     */
    public function getFieldSeparator()
    {
        return $this->fieldSeparator;
    }

    /**
     * Set field separator
     *
     * @param string $fieldSeparator
     * @return $this
     */
    public function setFieldSeparator($fieldSeparator)
    {
        $this->fieldSeparator = $fieldSeparator;
        return $this;
    }

    /**
     * Returns parent
     *
     * @return Query
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Overrides parent
     *
     * @return $this
     */
    public function setParent(Query $parent)
    {
        $this->parent = $parent;
        return $this;
    }


    /**
     * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::getParameters()
     */
    public function getParameters()
    {
        return $this->parent->getParameters();
    }

    /**
     * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::setParameters()
     */
    public function setParameters(array $parameters)
    {
        $this->parent->setParameters($parameters);
        return $this;
    }

    /**
     * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::addParameter()
     */
    public function addParameter($key, $value)
    {
        $this->parent->addParameter($key, $value);
        return $this;
    }

    /**
     * @see \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface::removeParameter()
     */
    public function removeParameter($key)
    {
        $this->parent->removeParameter($key);
        return $this;
    }



    /**
     * Ensures proper cloning of object properties
     */
    public function __clone()
    {
        parent::__clone();
        $this->orderBy = clone $this->orderBy;
        $this->groupBy = clone $this->groupBy;
    }
}
