<?php

namespace Innologi\Decosdata\Service\Option;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
abstract class OptionServiceAbstract
{
    // @LOW ___singleton?
    /**
     * @var array
     */
    protected $objectCache = [];

    /**
     * @var string
     */
    protected $optionNamespace;

    /**
     * @var integer
     */
    protected $index;

    /**
     * @var array
     */
    protected $optionVariables = [];

    public function __construct()
    {
        $this->optionNamespace = str_replace('OptionService', '', static::class);
    }

    /**
     * Returns current index
     *
     * @return integer
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Sets option variables
     *
     * @param string $option
     */
    public function setOptionVariables($option, array $vars = []): void
    {
        $this->optionVariables[$option] = $vars;
    }

    /**
     * Unsets option variables
     *
     * @param string $option
     */
    public function unsetOptionVariables($option): void
    {
        unset($this->optionVariables[$option]);
    }

    /**
     * Returns option variable
     *
     * @param string $option
     * @param string $var
     * @return mixed
     */
    public function getOptionVariable($option, $var)
    {
        // array_key_exists makes sure we support returning NULL values for existing $var s
        if (!(isset($this->optionVariables[$option]) && \array_key_exists($var, $this->optionVariables[$option]))) {
            throw new Exception\OptionException(1528817379, ['Option variable {' . $option . ':' . $var . '} does not exist.']);
        }
        return $this->optionVariables[$option][$var];
    }

    // @TODO ___debug to see if this is still a valid construction now that Query uses objects as $value
    /**
     * Executes the given optionMethod on the requested option.
     *
     * $option is passed as reference since args may contain .var elements that
     * are resolved when executed.
     *
     * @param string $optionMethod
     * @param mixed $subject
     * @return mixed
     */
    protected function executeOption($optionMethod, array &$option, $subject)
    {
        // validate args, detect and replace references to previously set option vars
        $option['args'] = isset($option['args']) && \is_array($option['args']) ? $this->detectAndReplaceArgumentVariables($option['args']) : [];

        return call_user_func_array(
            [$this->getOptionObject($option), $optionMethod],
            // @LOW ___if the service becomes a singleton, we could do away with the need to pass $this
            [$option['args'], $subject, $this],
        );
    }

    /**
     * Recursively detects .var elements in option arguments, and replaces it with requested var value
     *
     * @return array
     */
    protected function detectAndReplaceArgumentVariables(array $args)
    {
        foreach ($args as $name => &$arg) {
            // go through arrays but only if the arg isn't a renderOptions recursion
            if (is_array($arg) && $name !== 'renderOptions') {
                // replace if there is only a .var string element, or recursively continue detection
                $arg = isset($arg['var'][0]) && is_string($arg['var']) && \count($arg) === 1
                    ? $this->getOptionVariable(...explode(':', $arg['var'], 2))
                    : $this->detectAndReplaceArgumentVariables($arg);
            }
        }
        return $args;
    }

    /**
     * Get Option Object
     *
     * @throws Exception\MissingOption
     * @return object
     */
    protected function getOptionObject(array $option)
    {
        if (!isset($option['option'])) {
            throw new Exception\MissingOption(1448552481);
        }
        $className = $option['option'];
        if (!isset($this->objectCache[$className])) {
            $this->objectCache[$className] = $this->resolveOptionClass($className);
        }
        return $this->objectCache[$className];
    }

    /**
     * Resolves an option class by its class name
     *
     * If classname is given without namespace, the default option namespace is assumed.
     *
     * @param string $className
     * @return object
     * @throws Exception\MissingOptionClass
     * @throws Exception\InvalidOptionClass
     */
    protected function resolveOptionClass($className)
    {
        if (!str_contains($className, '\\')) {
            $className = $this->optionNamespace . '\\' . $className;
        }
        if (!class_exists($className)) {
            throw new Exception\MissingOptionClass(1448552497, [$className]);
        }
        $object = GeneralUtility::makeInstance($className);
        $interfaceClassName = $this->optionNamespace . '\\OptionInterface';
        if (!is_subclass_of($object, $interfaceClassName)) {
            throw new Exception\InvalidOptionClass(1449155186, [
                // since $object was retrieved via service container, we're not sure if $object Class === $className
                $object::class, $interfaceClassName,
            ]);
        }
        return $object;
    }
}
