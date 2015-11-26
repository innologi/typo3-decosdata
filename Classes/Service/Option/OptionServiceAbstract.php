<?php
namespace Innologi\Decosdata\Service\Option;
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
use Innologi\Decosdata\Service\Option\Exception\MissingOption;
use Innologi\Decosdata\Service\Option\Exception\MissingOptionClass;
/**
 * Option Service Abstract
 *
 * Handles the resolving and calling of option class/methods.
 *
 * Not designed to be a singleton?
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class OptionServiceAbstract {
	// @LOW ___singleton?
	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $objectCache = array();

	/**
	 * @var string
	 */
	protected $optionNamespace;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->optionNamespace = str_replace('OptionService', '', get_class($this));
	}

	/**
	 * Executes the given optionMethod on the requested option.
	 *
	 * @param string $optionMethod
	 * @param array $option
	 * @param mixed &$value
	 * @param object $parentObject
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Option\Exception\MissingOption
	 */
	protected function executeOption($optionMethod, array $option, &$value, $parentObject) {
		if ( !isset($option['option']) ) {
			throw new MissingOption(1448552481);
		}
		if ( !isset($option['args']) ) {
			$option['args'] = array();
		}
		$className = $option['option'];
		if (!isset($this->objectCache[$className])) {
			$this->objectCache[$className] = $this->resolveOptionClass($className);
		}
		call_user_func_array(
			array($this->objectCache[$className], $optionMethod),
			array($option['args'], &$value, $parentObject)
		);
	}

	/**
	 * Resolves an option class by its class name
	 *
	 * If classname is given without namespace, the default option namespace is assumed.
	 *
	 * @param string $className
	 * @return object
	 * @throws \Innologi\Decosdata\Service\Option\Exception\MissingOptionClass
	 */
	protected function resolveOptionClass($className) {
		if (strpos($className, '\\') === FALSE) {
			$className = $this->optionNamespace. '\\' . $className;
		}
		if (!class_exists($className)) {
			throw new MissingOptionClass(1448552497, array($className));
		}
		$object = $this->objectManager->get($className);
		return $object;
	}
}
