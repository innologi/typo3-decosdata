<?php
namespace Innologi\Decosdata\Service\QueryBuilder\Query\Part;
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
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintAnd;
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * Constraint Container abstract
 *
 * Extend this abstract for a class to have $constraint and associated methods.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class ConstraintContainer {

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface
	 */
	protected $constraint;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
	 */
	protected $constraintFactory;

	/**
	 * Returns constraints
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface
	 */
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Sets constraint
	 *
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintInterface $constraint
	 * @return $this
	 */
	public function setConstraint(ConstraintInterface $constraint) {
		$this->constraint = $constraint;
		return $this;
	}

	/**
	 * Adds a constraint to already existing ones.
	 *
	 * Note that there is only one constraint. This method will check if there already
	 * is one, and if so, creates an ConstraintAnd to which both constraints can be added.
	 *
	 * @param string $key
	 * @param ConstraintInterface $constraint
	 * @return $this
	 */
	public function addConstraint($key, ConstraintInterface $constraint) {
		if ($this->constraint !== NULL) {
			// an existing constraint means we need to add both constraints to a collection
			if ($this->constraint instanceof ConstraintAnd) {
				// but if our existing constraint already is a usable collection, just add to
				// it to save memory and performance
				$this->constraint->addConstraint($key, $constraint);
				return $this;
			} else {
				// @LOW _note that if $key === 'original', it would overwrite $this->constraint..
				$constraint = $this->getConstraintFactory()->createConstraintAnd(array(
					'original' => $this->constraint,
					$key => $constraint
				));
			}
		}
		return $this->setConstraint($constraint);
	}

	/**
	 * Returns ConstraintFactory.
	 *
	 * This construct is preferred over DI in this specific case, because we only ever need
	 * the constraintFactory added to this class if it is used, which is ON FEW OCCASSIONS
	 * only. So this saves us some memory and performance.
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
	 */
	protected function getConstraintFactory() {
		if ($this->constraintFactory === NULL) {
			$this->constraintFactory = GeneralUtility::makeInstance(ConstraintFactory::class);
		}
		return $this->constraintFactory;
	}

}
