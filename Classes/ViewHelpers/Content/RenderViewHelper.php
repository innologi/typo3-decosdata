<?php

namespace Innologi\Decosdata\ViewHelpers\Content;

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
use Innologi\Decosdata\Service\Option\RenderOptionService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

// @TODO ___use \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface ?
/**
 * Content.Render ViewHelper
 *
 * Renders content as directed by configuration.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RenderViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var RenderOptionService
     */
    protected $optionService;

    public function injectOptionService(RenderOptionService $optionService)
    {
        $this->optionService = $optionService;
    }

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('tag', 'string', 'Wraps content with optional tag.', false);
        $this->registerArgument('tagAttributes', 'array', 'Sets attributes to optional tag.', false, []);
        $this->registerArgument('options', 'array', 'Configuration directives for rendering content.', false, []);
        $this->registerArgument('item', 'array', 'Complete item.', false, []);
        $this->registerArgument('index', 'integer', 'Current content index.', true);
    }

    /**
     * @see \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::initialize()
     */
    public function initialize()
    {
        if ($this->renderingContext instanceof RenderingContextInterface) {
            $this->optionService->setRequest(
                $this->renderingContext->getRequest(),
            );
        }
    }

    /**
     * Renders content
     *
     * @param string $content
     * @return string
     */
    public function render($content = null)
    {
        if ($content === null) {
            $content = trim((string) $this->renderChildren());
        }

        // if configured, add an outer tag
        if ($this->hasArgument('tag')) {
            // we use the AddTag RenderOption instead of directly using the tagFactory,
            // so that other options can be used to influence said tag, like adding
            // a class or title or data-attribute, or whatever
            $this->arguments['options'][] = [
                'option' => 'AddTag',
                'args' => [
                    'name' => $this->arguments['tag'],
                    'attributes' => $this->arguments['tagAttributes'],
                ],
            ];
        }

        if (!empty($this->arguments['options'])) {
            // @TODO ___if we want to support field- en blob-less content, consider a render->content element referring to a content-# or a default 'current'. We would also have to have access to other content fields.
            $tag = $this->optionService->processOptions(
                $this->arguments['options'],
                $content,
                $this->arguments['index'],
                $this->arguments['item'],
            );
            return $tag->render();
        }

        return $content;
    }
}
