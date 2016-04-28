<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Frenck Lutke <http://www.frencklutke.nl>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Controller\CommandLineController;
use Innologi\Decosdata\Library\ExtUpdate\CliStarter;
/**
 * Starts Migrate Update script from CLI, if called from the TYPO3 CLI dispatcher.
 *
 * Example usage:
 * -> php ./typo3/cli_dispatch.phpsh decosdata:migrate
 *
 * @author Frenck Lutke <http://frencklutke.nl/>
 */
if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI && basename(PATH_thisScript) === 'cli_dispatch.phpsh') {

	/** @var CommandLineController $cli */
	$cli = GeneralUtility::makeInstance(CommandLineController::class);

	$message = '';
	try {
		/** @var CliStarter $extUpdater */
		$extUpdater = GeneralUtility::makeInstance(CliStarter::class);
		$extUpdater->executeUpdateIfNeeded('decosdata', $cli);
	} catch (\Exception $e) {
		$message = '[' . $e->getCode() . '] ' . $e->getMessage();
		$cli->cli_echo($message . PHP_EOL);
	}

} else {
	die('This script needs to be called from the TYPO3 CLI dispatcher' . PHP_EOL);
}