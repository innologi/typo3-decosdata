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
 * Constraint Collection
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ConstraintCollection implements ConstraintInterface, \Iterator {

	/**
	 * @var string
	 */
	protected $logic;

	/**
	 * @var array
	 */
	protected $constraints;

	/**
	 * Class constructor
	 *
	 * @param string $logic
	 * @param array $constraints
	 * @return $this
	 */
	public function __construct($logic, array $constraints = []) {
		$this->logic = $logic;
		$this->constraints = $constraints;
		return $this;
	}

	/**
	 * Returns logic
	 *
	 * @return string
	 */
	public function getLogic() {
		return $this->logic;
	}

	/**
	 * Returns constraints
	 *
	 * @return array
	 */
	public function getConstraints() {
		return $this->constraints;
	}

	/**
	 * Sets constraints
	 *
	 * @param array $constraints
	 * @return $this
	 */
	public function setConstraints(array $constraints) {
		$this->constraints = $constraints;
		return $this;
	}

	/**
	 * Adds constraint
	 *
	 * @param string $key
	 * @param ConstraintInterface $constraint
	 * @return $this
	 */
	public function addConstraint($key, ConstraintInterface $constraint) {
		$this->constraints[$key] = $constraint;
		return $this;
	}

	/**
	 * Removes constraint
	 *
	 * @param string $key
	 * @return $this
	 */
	public function removeConstraint($key) {
		if (isset($this->constraints[$key])) {
			unset($this->constraints[$key]);
		}
		return $this;
	}



	/**
	 * Ensures proper cloning of object properties
	 *
	 * @return void
	 */
	public function __clone() {
		foreach ($this->constraints as &$constraint) {
			$constraint = clone $constraint;
		}
	}



	/**************************
	 * Iterator implementation
	 **************************/

	public function current(): mixed {
		return current($this->constraints);
	}

	public function next(): void {
		next($this->constraints);
	}

	public function key(): mixed {
		return key($this->constraints);
	}

	public function valid(): bool {
		return current($this->constraints) !== FALSE;
	}

	public function rewind(): void {
		reset($this->constraints);
	}

}
