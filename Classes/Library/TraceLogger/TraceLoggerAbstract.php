<?php
namespace Innologi\Decosdata\Library\TraceLogger;
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
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
/**
 * Trace Logger Abstract
 *
 * @package InnologiLibs
 * @subpackage TraceLogger
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class TraceLoggerAbstract implements TraceLoggerInterface {

	/**
	 * @var integer
	 */
	protected $level = 0;

	/**
	 * {@inheritDoc}
	 * @see TraceLoggerInterface::getLevel()
	 */
	public function getLevel() {
		return $this->level;
	}

	/**
	 * {@inheritDoc}
	 * @see TraceLoggerInterface::setLevel()
	 */
	public function setLevel($level) {
		$this->level = $level;
	}

	/**
	 * {@inheritDoc}
	 * @see TraceLoggerInterface::getTrace()
	 */
	public function getTrace($depth = 1) {
		$traceEntry = [
			'call' => '',
			'args' => []
		];

		$e = new \Exception;
		$trace = $e->getTrace();
		$caller = $trace[$depth];
		$traceEntry['call'] = substr($caller['class'], strrpos($caller['class'], '\\') + 1) . $caller['type'] . $caller['function'] . ':' . $caller['line'];
		foreach ($caller['args'] as $index => $arg) {
			$argString = '  [' . $index . '] ';
			if (is_array($arg)) {
				$argString .= json_encode($arg, JSON_UNESCAPED_SLASHES);
			} elseif (is_object($arg)) {
				$argString .= (new \ReflectionClass($arg))->getShortName();
				if ($arg instanceof AbstractDomainObject) {
					$argString .= ':' . $arg->getUid();
				}
			} else {
				$argString .= $arg;
			}
			$traceEntry['args'][] = $argString;
		}

		return $traceEntry;
	}

}
