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
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryField;

/**
 * DateConversion option
 *
 * Converts a date content field through the MySQL function DATE_FORMAT().
 * @see https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DateConversion extends OptionAbstract
{
    // @TODO ___see if the mixed input argument still needs to be supported now that we can apply options per field

    /**
     * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryField()
     */
    public function alterQueryField(array $args, QueryField $queryField, QueryOptionService $service)
    {
        if (!isset($args['format'])) {
            // @TODO ___throw exception.. or should it be optional?
        }

        $id = $queryField->getId() . 'dateconversion' . $service->getOptionIndex();
        $parameterKey = ':' . $id;
        $select = $queryField->getSelect();
        $select->addWrap($id, 'DATE_FORMAT(' . $select->getWrapDivider() . ', ' . $parameterKey . ')');
        $queryField->addParameter($parameterKey, $args['format']);
    }
}
