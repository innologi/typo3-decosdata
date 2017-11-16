<?php
namespace Innologi\Decosdata\ViewHelpers;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
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
class CrumbPathViewHelper extends AbstractViewHelper {
	// @LOW _can we bring the user back in a breadcrumb-location to the exact URL that he originated from?
	// @TODO ___what about link titles?

	/**
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @var \Innologi\Decosdata\Service\BreadcrumbService
	 * @inject
	 */
	protected $breadcrumbService;

	/**
	 * @var \Innologi\Decosdata\Service\ParameterService
	 * @inject
	 */
	protected $parameterService;

	/**
	 * @var integer
	 */
	protected $currentLevel;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->registerArgument('partial', 'string', 'Dedicated partial template override.', FALSE, 'ViewHelpers/CrumbPath');
		$this->registerArgument('renderAbove', 'boolean', 'Renders crumbpath above content.', FALSE, TRUE);
		$this->registerArgument('renderBelow', 'boolean', 'Renders crumbpath below content.', FALSE, TRUE);
	}

	/**
	 * Render Crumbpath
	 *
	 * @return string
	 */
	public function render() {
		// render crumbpath only if active
		if ( !$this->breadcrumbService->isActive() ) {
			return $this->renderChildren();
		}

		// render a specific partial that exists for this sole purpose
		return $this->viewHelperVariableContainer->getView()->renderPartial(
			$this->arguments['partial'],
			NULL,
			[
				'renderAbove' => $this->arguments['renderAbove'],
				'renderBelow' => $this->arguments['renderBelow'],
				'crumbPath' => $this->buildCrumbPathConfiguration(),
				// requires the use of format.raw VH, which costs us ~1.6 ms on average, but keeps us
				// from using a marker like ###CONTENT### with str_replace, which can easily be fooled
				'content' => $this->renderChildren()
			]
		);
	}

	/**
	 * Build crumbpath template configuration arguments
	 *
	 * @return array
	 * @throws \Innologi\Decosdata\Exception\PaginationError
	 */
	protected function buildCrumbPathConfiguration() {
		$this->currentLevel = $this->breadcrumbService->getCurrentLevel();
		$labelMap = $this->breadcrumbService->getCrumbLabelMap();
		$configuration = [
			'current' => [
				'label' => $labelMap[$this->currentLevel]
			],
			'crumbs' => []
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
	protected function createCrumbElement($level, $label) {
		$exclude = [$this->parameterService->wrapInPluginNamespace('page')];
		for ($i=$this->currentLevel; $i>$level; $i--) {
			$exclude[] = $this->parameterService->wrapInPluginNamespace('_' . $i);
		}
		return [
			'label' => $label,
			'exclude' => $exclude,
			'arguments' => ['level' => $level]
		];
	}

}
