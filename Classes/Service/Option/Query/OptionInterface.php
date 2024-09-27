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
use Innologi\Decosdata\Service\Option\QueryOptionService;
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent;
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryField;

/**
 * Query Option Interface
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface OptionInterface
{
    // @TODO __consider that for TCA to offer some kind of interface to apply queryOptions with, it could be helpful to NOT enforce these options, put a method_exists check in the optionService, and detect the available uses by which of these methods exist per option
    /**
     * Alters query through $queryField on the field level.
     * Influences a specific column field.
     */
    public function alterQueryField(array $args, QueryField $queryField, QueryOptionService $service);

    /**
     * Alters query through $queryContent on the column level.
     * Influences all fields of a column.
     */
    public function alterQueryColumn(array $args, QueryContent $queryContent, QueryOptionService $service);

    /**
     * Alters query through $query on the row level.
     * Influences the entire row of columns.
     */
    public function alterQueryRow(array $args, Query $query, QueryOptionService $service);
}
