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
 * Constraint Abstract
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class ConstraintAbstract implements ConstraintInterface {

	/**
	 * @var string
	 */
	protected $localField;

	/**
	 * @var string
	 */
	protected $localAlias;

	/**
	 * @var string
	 */
	protected $operator;

	/**
	 * Returns Local Field
	 *
	 * @return string
	 */
	public function getLocalField() {
		return $this->localField;
	}

	/**
	 * Sets Local Field
	 *
	 * @param string $localField
	 * @return $this
	 */
	public function setLocalField($localField) {
		$this->localField = $localField;
		return $this;
	}

	/**
	 * Returns Local Table Alias
	 *
	 * @return string
	 */
	public function getLocalAlias() {
		return $this->localAlias;
	}

	/**
	 * Sets Local Table Alias
	 *
	 * @param string $localAlias
	 * @return $this
	 */
	public function setLocalAlias($localAlias) {
		$this->localAlias = $localAlias;
		return $this;
	}

	/**
	 * Returns comparison operator
	 *
	 * @return string
	 */
	public function getOperator() {
		return $this->operator;
	}

	/**
	 * Sets comparison operator
	 *
	 * @param string $operator
	 * @return $this
	 */
	public function setOperator($operator) {
		$this->operator = $operator;
		return $this;
	}


}
