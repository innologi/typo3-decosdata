<?php
namespace Innologi\Decosdata\Service\Option\Render\Traits;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\Exception\OptionException;
 /**
 * Command Runner Trait
 *
 * Offers basic functionality to execute a system command and catch its output.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait CommandRunner {

	/**
	 * @var string
	 */
	protected $lastRunCommand;

	/**
	 * Run the command with any given variables substituted,
	 * and return the output.
	 *
	 * @param string $cmd
	 * @param array $variables
	 * @return array
	 */
	protected function runCommand($cmd, array $variables = []) {
		if (!empty($variables)) {
			foreach ($variables as $var => $value) {
				$cmd = str_replace('$' . $var, '\'' . $value . '\'', $cmd);
			}
		}

		$this->lastRunCommand = $cmd;
		$cmdOutput = NULL;
		$cmdStatus = NULL;
		exec(escapeshellcmd($cmd), $cmdOutput, $cmdStatus);

		if ($cmdStatus !== 0) {
			// anything else is an error exit code
			throw new OptionException(1524141893, ['Failed to run ' . \get_class($this) . ' command.']);
			// @LOW log $cmd + $cmdOutput + $cmdStatus
		}

		return $cmdOutput;
	}

}
