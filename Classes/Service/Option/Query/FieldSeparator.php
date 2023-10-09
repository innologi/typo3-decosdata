<?php
namespace Innologi\Decosdata\Service\Option\Query;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\QueryOptionService;
/**
 * FieldSeparator option
 *
 * Overrides field separator for multi-field content.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FieldSeparator extends OptionAbstract {

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryColumn()
	 */
	public function alterQueryColumn(array $args, QueryContent $queryContent, QueryOptionService $service) {
		if (!isset($args['separator'])) {
			// @TODO ___throw exception
		}
		// @TODO use a regular expression to match whitespace PLEASE
		if (isset($args['separator'][4]) && $args['separator'][0] === '|' && str_ends_with((string) $args['separator'], '|')) {
			$args['separator'] = substr((string) $args['separator'], 1, strlen((string) $args['separator'])-2);
		}
		$queryContent->setFieldSeparator($args['separator']);
	}

}
