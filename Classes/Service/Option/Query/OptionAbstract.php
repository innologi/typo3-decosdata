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
use Innologi\Decosdata\Service\Option\Exception\AlterQueryFieldDenied;
use Innologi\Decosdata\Service\Option\Exception\AlterQueryColumnDenied;
use Innologi\Decosdata\Service\Option\Exception\AlterQueryRowDenied;
/**
 * Query Option Abstract
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class OptionAbstract implements OptionInterface {

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryField()
	 * @throws \Innologi\Decosdata\Service\Option\Exception\AlterQueryFieldDenied
	 */
	public function alterQueryField(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		throw new AlterQueryFieldDenied(1448551244, array(get_class($this)));
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryColumn()
	 * @throws \Innologi\Decosdata\Service\Option\Exception\AlterQueryColumnDenied
	 */
	public function alterQueryColumn(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		throw new AlterQueryColumnDenied(1448551259, array(get_class($this)));
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Query\OptionInterface::alterQueryRow()
	 * @throws \Innologi\Decosdata\Service\Option\Exception\AlterQueryRowDenied
	 */
	public function alterQueryRow(array $args, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		throw new AlterQueryRowDenied(1448551286, array(get_class($this)));
	}

}
