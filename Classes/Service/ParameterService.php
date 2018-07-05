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
use Innologi\Decosdata\Exception\MissingParameter;
use TYPO3\CMS\Core\SingletonInterface;
/**
 * Parameter Service
 *
 * Handles all request-parameter validation and retrieval
 * and should be used as the only point of interaction with
 * parameter values.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ParameterService implements SingletonInterface {
	// @LOW ___add support for other extension parameters?

	/**
	 * @var boolean
	 */
	protected $__initialized = FALSE;

	/**
	 * @var string
	 */
	protected $pluginNameSpace;

	/**
	 * @var array
	 */
	protected $parameterCache = [];

	/**
	 * @var array
	 */
	protected $arguments = [];

	/**
	 * @var array
	 */
	protected $levelParameters;

	/**
	 * Initializes through a request object
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Web\Request $request
	 * @return $this
	 */
	public function initializeByRequest(\TYPO3\CMS\Extbase\Mvc\Web\Request $request) {
		if ($this->__initialized !== TRUE) {
			/** @var \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService */
			$extensionService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				\TYPO3\CMS\Extbase\Object\ObjectManager::class
			)->get(\TYPO3\CMS\Extbase\Service\ExtensionService::class);
			$this->pluginNameSpace = $extensionService->getPluginNamespace(
				$request->getControllerExtensionName(),
				$request->getPluginName()
			);

			$this->arguments = $request->getArguments();
			$this->__initialized = TRUE;
		}
		return $this;
	}

	/**
	 * Initializes through given extension and plugin names
	 *
	 * @param string $extensionName
	 * @param string $pluginName
	 * @return $this
	 */
	public function initialize($extensionName, $pluginName) {
		if ($this->__initialized !== TRUE) {
			/** @var \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService */
			$extensionService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				\TYPO3\CMS\Extbase\Object\ObjectManager::class
			)->get(\TYPO3\CMS\Extbase\Service\ExtensionService::class);
			$this->pluginNameSpace = $extensionService->getPluginNamespace($extensionName, $pluginName);

			$this->arguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged($this->pluginNamespace);
			$this->__initialized = TRUE;
		}
		return $this;
	}

	/**
	 * Confirms whether the requested parameter is available
	 * in the current request.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasParameter($name) {
		return isset($this->arguments[$name]);
	}

	/**
	 * Returns a parameter UNSAFE and raw (after urldecoding)
	 * without further validation or safeguards.
	 *
	 * @param string $name
	 * @return string
	 */
	public function getParameterRaw($name) {
		return rawurldecode($this->arguments[$name]);
	}

	/**
	 * Returns a parameter SAFE after validation.
	 *
	 * @param string $name
	 * @throws MissingParameter
	 * @return integer
	 */
	public function getParameterValidated($name) {
		if (!$this->hasParameter($name)) {
			throw new MissingParameter(1510677630, [$name]);
		}
		return (int) $this->getParameterRaw($name);
	}

	/**
	 * Returns a parameter SAFE after normalization, meaning if it does
	 * not exist or is not an expected value of 1+, it will be normalized
	 * to a value of 1 before it is returned.
	 *
	 * @param string $name
	 * @return integer
	 */
	public function getParameterNormalized($name) {
		if (!isset($this->parameterCache[$name])) {
			if ($this->hasParameter($name)) {
				$param = (int) $this->getParameterRaw($name);
				$this->parameterCache[$name] = $param > 0 ? $param : 1;
			} else {
				$this->parameterCache[$name] = 1;
			}
		}
		return $this->parameterCache[$name];
	}

	/**
	 * Returns a SAFE parameter from a (potential) multi-parameter setup.
	 * Values are pipe-separated (e.g. 123|456). You can request all values
	 * by simply requesting the original parameter name (i.e. _2 gets you
	 * 123|456), and you may also request a single out of multiple value
	 * by also specifying its index number (i.e. _2.0 and _2.1 get you 123
	 * and 456 respectively).
	 *
	 * @param string $name
	 * @throws MissingParameter
	 * @return integer|string
	 */
	public function getMultiParameter($name) {
		if (!isset($this->parameterCache[$name])) {
			// does part 0 exist?
			$nParts = explode('.', $name);
			if (!$this->hasParameter($nParts[0])) {
				throw new MissingParameter(1510679677, [$nParts[0]]);
			}
			// does part 1 (if requested) exist?
			$paramParts = explode('|', $this->getParameterRaw($nParts[0]));
			if (isset($nParts[1]) && !isset($paramParts[(int)$nParts[1]])) {
				throw new MissingParameter(1510679678, [$name]);
			}
			// cast to integer and cache every available value from param
			if (isset($paramParts[1])) {
				foreach ($paramParts as $index => &$paramPart) {
					$this->parameterCache[$nParts[0] . '.' . $index] = $paramPart = (int)$paramPart;
				}
				$this->parameterCache[$nParts[0]] = join('|', $paramParts);
			} else {
				$this->parameterCache[$name] = (int)$paramParts[0];
			}
		}
		return $this->parameterCache[$name];
	}

	/**
	 * Wraps a parameter name in the plugin namespace
	 *
	 * @param string $name
	 * @return string
	 */
	public function wrapInPluginNamespace($name) {
		return $this->pluginNameSpace . '[' . $name . ']';
	}

	/**
	 * Encodes a parameter value for usage in URLs.
	 *
	 * @param string $value
	 * @return string
	 */
	public function encodeParameter($value) {
		return rawurlencode($value);
	}

	/**
	 * Returns level parameters, including 'level'
	 *
	 * @return array
	 */
	public function getLevelParameters() {
		if ($this->levelParameters === NULL) {
			$this->levelParameters = [];
			if ($this->hasParameter('level')) {
				$this->levelParameters['level'] = $this->getParameterValidated('level');
			}
			$args = \array_keys($this->arguments);
			foreach ($args as $name) {
				if (isset($name[1]) && $name[0] === '_' && is_numeric(substr($name, 1))) {
					$this->levelParameters[$name] = $this->getParameterValidated($name);
				}
			}
		}
		return $this->levelParameters;
	}

}
