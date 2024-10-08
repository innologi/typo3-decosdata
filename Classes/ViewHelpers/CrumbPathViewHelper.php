<?php

namespace Innologi\Decosdata\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\BreadcrumbService;
use Innologi\Decosdata\Service\ParameterService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

// @LOW ___use \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface ?
/**
 * Crumbpath ViewHelper
 *
 * Produces a crumbpath of choice.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CrumbPathViewHelper extends AbstractViewHelper
{
    // @LOW _can we bring the user back in a breadcrumb-location to the exact URL that he originated from?
    // @TODO ___what about link titles?

    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var BreadcrumbService
     */
    protected $breadcrumbService;

    /**
     * @var ParameterService
     */
    protected $parameterService;

    /**
     * @var integer
     */
    protected $currentLevel;

    public function injectBreadcrumbService(BreadcrumbService $breadcrumbService): void
    {
        $this->breadcrumbService = $breadcrumbService;
    }

    public function injectParameterService(ParameterService $parameterService): void
    {
        $this->parameterService = $parameterService;
    }

    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('partial', 'string', 'Dedicated partial template override.', false, 'ViewHelpers/CrumbPath');
        $this->registerArgument('renderAbove', 'boolean', 'Renders crumbpath above content.', false, true);
        $this->registerArgument('renderBelow', 'boolean', 'Renders crumbpath below content.', false, true);
    }

    /**
     * Render Crumbpath
     *
     * @return string
     */
    public function render()
    {
        // render crumbpath only if active
        if (!$this->breadcrumbService->isActive()) {
            return $this->renderChildren();
        }

        // render a specific partial that exists for this sole purpose
        return $this->viewHelperVariableContainer->getView()->renderPartial(
            $this->arguments['partial'],
            null,
            [
                'renderAbove' => $this->arguments['renderAbove'],
                'renderBelow' => $this->arguments['renderBelow'],
                'crumbPath' => $this->buildCrumbPathConfiguration(),
                // requires the use of format.raw VH, which costs us ~1.6 ms on average, but keeps us
                // from using a marker like ###CONTENT### with str_replace, which can easily be fooled
                'content' => $this->renderChildren(),
            ],
        );
    }

    /**
     * Build crumbpath template configuration arguments
     *
     * @return array
     * @throws \Innologi\Decosdata\Exception\PaginationError
     */
    protected function buildCrumbPathConfiguration()
    {
        $this->currentLevel = $this->breadcrumbService->getCurrentLevel();
        $labelMap = $this->breadcrumbService->getCrumbLabelMap();
        $configuration = [
            'current' => [
                'label' => $labelMap[$this->currentLevel],
            ],
            'crumbs' => [],
        ];
        unset($labelMap[$this->currentLevel]);

        foreach ($labelMap as $level => $label) {
            $configuration['crumbs'][] = $this->createCrumbElement($level, $label);
        }
        return $configuration;
    }

    /**
     * Creates a crumb element
     *
     * @param integer $level
     * @param string $label
     * @return array
     */
    protected function createCrumbElement($level, $label)
    {
        $exclude = [
            // @TODO I'm doing this on multiple locations, so I should just put it in configuration somewhere and let parameterService do the rest
            $this->parameterService->wrapInPluginNamespace('page'),
            $this->parameterService->wrapInPluginNamespace('search'),
        ];
        for ($i = $this->currentLevel; $i > $level; $i--) {
            $exclude[] = $this->parameterService->wrapInPluginNamespace('_' . $i);
        }
        // the first level is the default, we should be able to use the clean (original) page link for that
        return $level === 1 ? [
            'label' => $label,
            'cleanLink' => true,
        ] : [
            'label' => $label,
            'exclude' => $exclude,
            'arguments' => [
                'level' => $level,
            ],
        ];
    }
}
