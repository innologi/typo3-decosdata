<?php
namespace Innologi\Decosdata\Service;
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
use TYPO3\CMS\Core\SingletonInterface;
/**
 * Condition Service
 *
 * Meant to offer a basic API for conditionals in plugin configurations.
 * For now it is very limited, and might have some redundancy until further
 * refactoring of the other service classes.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ConditionService implements SingletonInterface {
	use Option\Render\Traits\ItemAccess;

	/**
	 * @var \Innologi\Decosdata\Service\Option\RenderOptionService
	 */
	protected $renderOptionService;

	/**
	 * Stored results
	 * @var array
	 */
	protected $ifMatch = [];

	public function setRenderOptionService(\Innologi\Decosdata\Service\Option\RenderOptionService $renderOptionService) {
		$this->renderOptionService = $renderOptionService;
		return $this;
	}

	/**
	 * Check whether an if-configuration matches
	 *
	 * @param array $if
	 * @param integer $index
	 * @param string $cIndex
	 * @return boolean
	 */
	public function ifMatch(array $if, $index, $cIndex) {
		$result = FALSE;

		// make sure this array exists
		if (!isset($this->ifMatch[$cIndex])) {
			$this->ifMatch[$cIndex] = [];
		}

		if (isset($if['ifMatch']) && is_array($if['ifMatch'])) {
			$result = count(array_intersect_assoc($this->ifMatch[$cIndex], $if['ifMatch'])) === count($if['ifMatch']);
		} elseif (isset($if['constraint']) && is_array($if['constraint'])) {
			$result = $this->processConstraint($if['constraint'], isset($if['matchAll']) && (bool)$if['matchAll']);
		} elseif (isset($if['source'])) {
			$result = $this->processConstraint([$if]);
		}
		// we store it in a specific format we can use array_intersect_assoc on
		$this->ifMatch[$cIndex][$index] = (string) ((int) $result);
		return $result;
	}
	// @TODO add checks for operators / values similar to FilterItems, preferably using a common lib class?
	/**
	 * Processes an array of constraints with regards to the MatchAll flag (=AND/OR)
	 *
	 * @param array $constraints
	 * @param boolean $matchAll
	 * @return boolean
	 */
	protected function processConstraint(array $constraints, $matchAll = FALSE) {
		$result = FALSE;
		foreach ($constraints as $constraint) {
			if (isset($constraint['constraint']) && is_array($constraint['constraint'])) {
				$result = $this->processConstraint($constraint['constraint'], isset($constraint['matchAll']) && (bool)$constraint['matchAll']);
			} elseif (! (isset($constraint['source']) && isset($constraint['operator']) && isset($constraint['value'])) ) {
				// @TODO throw exception
			} else {
				// note that the typecasts will have the effect of NULL === ''
				$source = (string) $this->itemAccess($constraint['source'], $this->renderOptionService);
				$value = (string) ($constraint['value'] === 'NULL' ? NULL : $constraint['value']);
				switch ($constraint['operator']) {
					case '=':
						$result = $source === $value;
						break;
					case '!=':
						$result = $source !== $value;
						break;
					default:
						// @TODO throw exception
				}
			}
			if ($matchAll !== $result) {
				// AND breaks on first FALSE, OR breaks on first TRUE
				break;
			}
		}
		return $result;
	}

}
