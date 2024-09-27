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
 * Extension Configuration Trait
 *
 * Offers basic functionality to retrieve extension configuration.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait ExtensionConfiguration
{
    /**
     * @var string
     */
    protected $extensionKey = 'decosdata';

    /**
     * @var array
     */
    protected $extensionConfiguration;

    /**
     * Retrieve extension configuration by key
     *
     * @param string $key
     * @return array
     */
    protected function getExtensionConfiguration($key)
    {
        if ($this->extensionConfiguration === null) {
            $this->extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class,
            )->get($this->extensionKey);
        }
        if (!isset($this->extensionConfiguration[$key])) {
            throw new OptionException(1525269917, ['Extension Configuration key \'' . $key . '\' missing.']);
        }
        return $this->extensionConfiguration[$key];
    }
}
