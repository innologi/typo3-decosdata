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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

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
    use CompileWithRenderStatic;

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var \Innologi\Decosdata\Service\RuntimeStorageService
     */
    protected static $storageService;

    /**
     * Get Storage Service
     *
     * @return \Innologi\Decosdata\Service\RuntimeStorageService
     */
    protected static function getStorageService()
    {
        if (self::$storageService === null) {
            self::$storageService = GeneralUtility::makeInstance(RuntimeStorageService::class);
        }
        return self::$storageService;
    }

    /**
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $id = 'RenderViewHelper-' . self::getStorageService()->generateHash($arguments);
        if (self::getStorageService()->has($id)) {
            $output = self::getStorageService()->get($id);
        } else {
            $output = parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);
            self::getStorageService()->set($id, $output);
        }
        return $output;
    }
}
