<?php

namespace Innologi\Decosdata\ViewHelpers;

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
use Innologi\Decosdata\Service\RuntimeStorageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render ViewHelper
 *
 * Improves upon the original RenderViewHelper by utilizing the RuntimeStorageService.
 * This improves performance when a partial or section is rendered multiple times with
 * the same arguments.
 *
 * Obvious use cases are Fluid's Pagination Widget, or decosdata's PageBrowser ViewHelper.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RenderViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var \Innologi\Decosdata\Service\RuntimeStorageService
     */
    protected $storageService;

    /**
     * Get Storage Service
     *
     * @return \Innologi\Decosdata\Service\RuntimeStorageService
     */
    protected function getStorageService()
    {
        if ($this->storageService === null) {
            $this->storageService = GeneralUtility::makeInstance(RuntimeStorageService::class);
        }
        return $this->storageService;
    }

    /**
     * @return mixed
     */
    public function render()
    {
        $id = 'RenderViewHelper-' . $this->getStorageService()->generateHash($this->arguments);
        if ($this->getStorageService()->has($id)) {
            $output = $this->getStorageService()->get($id);
        } else {
            $output = parent::render();
            $this->getStorageService()->set($id, $output);
        }
        return $output;
    }
}
