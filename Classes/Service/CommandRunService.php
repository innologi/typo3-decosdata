<?php
namespace Innologi\Decosdata\Service;
/***************************************************************
 * Copyright notice
 *
 * (c) 2018 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\SingletonInterface;
use Innologi\Decosdata\Exception\CmdError;
/**
 * Command Run Service
 *
 * Offers secure methods to execute a system command and catch its output
 * while offering control on the amount of flexibility.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CommandRunService implements SingletonInterface {

	/**
	 * @var string
	 */
	protected $lastRunCommand;

	/**
	 * Needs to be provided by Option class, or it won't be able to run any
	 * command at all.
	 * - NEVER EVER allow rm, cp, mv or equally destructive alternatives!
	 * - Use variable replacement for grep, ls, awk or equally revealing
	 * alternatives!
	 * - Supports wildcard character for start or end of a binary name, e.g.
	 * pdf* (SLOW!)
	 *
	 * @var array
	 */
	protected $allowBinaries = [];

	/**
	 * Command substitutes with support for 1 argument and 1 eval funcion.
	 * Should be used as a safe alternative to allowing binaries that are
	 * potentially dangerous or revealing, e.g.:
	 * - 'GREP:$1:escapeshellarg' => 'grep $1'
	 * - 'AWKPRINT:$1:intval' => 'awk \'{print $$1}\''
	 *
	 * @var array
	 */
	protected $commandSubstitutes = [];

	/**
	 * For piping output
	 *
	 * @var integer
	 */
	protected $commandLimit = 1;

	/**
	 * @var array
	 */
	protected $evaluatedCommandHashMap = [];

	/**
	 * Resets service parameters (but not cached commands or last run command)
	 *
	 * @return $this
	 */
	public function reset() {
		$this->allowBinaries = [];
		$this->commandSubstitutes = [];
		$this->commandLimit = 1;
		return $this;
	}

	/**
	 * Sets Allowed Binaries
	 *
	 * @param array $allowBinaries
	 * @return $this
	 */
	public function setAllowBinaries(array $allowBinaries) {
		$this->allowBinaries = $allowBinaries;
		return $this;
	}

	/**
	 * Sets Command substitutes
	 *
	 * @param array $commandSubstitutes
	 * @return $this
	 */
	public function setCommandSubstitutes(array $commandSubstitutes) {
		$this->commandSubstitutes = $commandSubstitutes;
		return $this;
	}

	/**
	 * Sets piping command limit
	 *
	 * @param integer $commandLimit
	 * @return $this
	 */
	public function setCommandLimit($commandLimit) {
		$this->commandLimit = $commandLimit;
		return $this;
	}

	/**
	 * Returns last run command
	 *
	 * @return string
	 */
	public function getLastRunCommand() {
		return $this->lastRunCommand;
	}

	/**
	 * Run the command with any given variables substituted,
	 * and return the output.
	 *
	 * @param string $cmd
	 * @param array $variables
	 * @throws CmdError
	 * @return array
	 */
	public function runCommand($cmd, array $variables = []) {
		$cmd = $this->evaluateCommand($cmd);
		// set vars
		if (!empty($variables)) {
			foreach ($variables as $var => $value) {
				// note that after evaluation, $-signs will have been escaped
				$cmd = \str_replace('\\$' . $var, \escapeshellarg((string) $value), $cmd);
			}
		}
		$this->lastRunCommand = $cmd;

		$cmdOutput = [];
		$cmdStatus = NULL;
		\exec($cmd, $cmdOutput, $cmdStatus);

		if ($cmdStatus !== 0) {
			// anything else is an error exit code
			throw new CmdError(1524141893, [
				$cmd,
				'exit code ' . $cmdStatus . ', output: "' . join(PHP_EOL, $cmdOutput) . '"'
			]);
			// @LOW log $cmd + $cmdOutput + $cmdStatus
		}

		return $cmdOutput;
	}

	// @TODO add CacheManager cache get/set
	/**
	 * Evaluates the command and replaces command-substitutes
	 *
	 * @param string $cmd
	 * @throws CmdError
	 * @return string
	 */
	protected function evaluateCommand($cmd) {
		$hash = \md5($cmd);
		if (!isset($this->evaluatedCommandHashMap[$hash])) {

			$pipedCommands = \explode(' | ', $cmd, $this->commandLimit);
			foreach ($pipedCommands as &$command) {
				$command = \trim($command);
				if (isset($this->commandSubstitutes) && str_starts_with($command, '$')) {
					// the command is a substitute
					$command = $this->substituteCommand($command);
					continue;
				} elseif (isset($this->allowBinaries)) {
					// the command is NOT a subtitute
					[$baseBinary, $args] = \explode(' ', $command, 2);
					if ($this->isBinaryAllowed(\basename($baseBinary))) {
						$command = \escapeshellcmd($command);
						continue;
					}
					throw new CmdError(1525362390, [basename($baseBinary), 'unknown binary']);
				}
			}

			// 2>&1: make sure to catch error output
			$this->evaluatedCommandHashMap[$hash] = join(' | ', $pipedCommands) . ' 2>&1';
		}
		return $this->evaluatedCommandHashMap[$hash];
	}

	/**
	 * Returns whether the binary is defined in $allowBinaries
	 *
	 * @param string $binary
	 * @return boolean
	 */
	protected function isBinaryAllowed($binary) {
		if (\in_array($binary, $this->allowBinaries, TRUE)) {
			return TRUE;
		}
		// check if any allowed binary has a wildcard, and if yes, if the binary matches then
		foreach ($this->allowBinaries as $allowedBinary) {
			if (($pos = \strpos((string) $allowedBinary, '*')) !== FALSE && (
				($pos > 0 && str_starts_with($binary, \substr((string) $allowedBinary, 0, $pos - 1))) ||
				($pos === 0 && \strpos($binary, \substr((string) $allowedBinary, 1)) > 0)
			)) {
				return TRUE;
			}
		}
		// no matches found
		return FALSE;
	}

	/**
	 * Attempts to replace a substitute-command, throws exception if it fails
	 *
	 * @param string $cmd
	 * @throws CmdError
	 * @return string
	 */
	protected function substituteCommand($cmd) {
		// run through every available sub until we have a match
		foreach ($this->commandSubstitutes as $var => $substitute) {
			[$searchVar, $searchArg, $evalFunc] = \explode(':', $var, 3);
			if (str_starts_with($cmd, '$' . $searchVar)) {
				// return the substitute
				$replaceArg = \substr($cmd, \strlen($searchVar) + 2);
				return \str_replace(
					$searchArg,
					isset($evalFunc) ? \call_user_func($evalFunc, $replaceArg) : $replaceArg,
					(string) $substitute
				);
			}
		}
		// no matches found
		throw new CmdError(1525362280, [$cmd, 'unknown substitute']);
	}
}
