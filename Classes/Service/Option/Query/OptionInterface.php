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
use Innologi\Decosdata\Service\QueryBuilder\QueryBuilder;
/**
 * Query Option Interface
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface OptionInterface {

	/**
	 * Alters query through $queryConfiguration reference and $queryBuilder instance
	 * on the field level. Influences a specific column field.
	 *
	 * @param array $args
	 * @param array &$queryConfiguration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder $queryBuilder
	 * @return void
	 */
	public function alterQueryField(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder);

	/**
	 * Alters query through $queryConfiguration reference and $queryBuilder instance
	 * on the column level. Influences all fields of a column.
	 *
	 * @param array $args
	 * @param array &$queryConfiguration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder $queryBuilder
	 * @return void
	 */
	public function alterQueryColumn(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder);

	/**
	 * Alters query through $queryConfiguration reference and $queryBuilder instance
	 * on the row level. Influences the entire row of columns.
	 *
	 * @param array $args
	 * @param array &$queryConfiguration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder $queryBuilder
	 * @return void
	 */
	public function alterQueryRow(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder);

}
