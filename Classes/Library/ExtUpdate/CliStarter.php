<?php
namespace Innologi\Decosdata\Library\ExtUpdate;
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
use TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * Ext Update Abstract
 *
 * @package InnologiLibs
 * @subpackage ExtUpdate
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CliStarter extends UpdateScriptUtility {
	// @TODO ___doc entire class
	/**
	 * Returns true, if ext_update class says it wants to run.
	 *
	 * @param string $extensionKey extension key
	 * @return mixed NULL, if update is not available, else update script return
	 */
	public function executeUpdateIfNeeded($extensionKey, $cli) {
		$className = $this->requireUpdateScript($extensionKey);
		$scriptObject = GeneralUtility::makeInstance($className, $cli);
		return $scriptObject->access() ? $scriptObject->main() : NULL;
	}

}