<?php
namespace Innologi\Decosdata\Controller;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * Item controller
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemController extends ActionController {

	/**
	 * @var \Innologi\Decosdata\Service\TypeProcessorService
	 * @inject
	 */
	protected $typeProcessor;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder
	 * @inject
	 */
	protected $queryBuilder;

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
	 * @var array
	 */
	protected $activeConfiguration;

	/**
	 * @var array
	 */
	protected $import;

	/**
	 * @var integer
	 */
	protected $level = 1;

	/**
	 * {@inheritDoc}
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
	 */
	protected function initializeAction() {
		$this->parameterService->setRequest($this->request);
		$this->level = $this->parameterService->getParameterNormalized('level');

		// set imports, stage 1: find flexform override before TS overrides get in effect
		if (isset($this->settings['override']['import'][0])) {
			$this->import = GeneralUtility::intExplode(',', $this->settings['override']['import'], TRUE);
		}

		// @LOW cache?
		// check override TS
		if (isset(trim($this->settings['override']['ts'])[0])) {
			/** @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser $tsParser */
			$tsParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
			$tsParser->parse($this->settings['override']['ts']);
			// completely replace original settings
			$this->settings = $this->typeProcessor
				->getTypoScriptService()
				->convertTypoScriptArrayToPlainArray($tsParser->setup);
		}

		// set imports, stage 2: no flexform overrides? get it from TS
		if ($this->import === NULL) {
			$this->import = $this->settings['import'] ?? [];
		}

		// initialize breadcrumb
		if (isset($this->settings['breadcrumb']) && is_array($this->settings['breadcrumb'])) {
			// @LOW this being optional, means I probably shouldn't inject it
			$this->breadcrumbService->configureBreadcrumb($this->settings['breadcrumb'], $this->import);
		}

		#if ($this->request->hasArgument('_' . $this->level)) {
			// @TODO what to do with this one?
		#	$levelParameter = $this->request->getArgument('_' . $this->level);
		#}

		// @LOW ___will probably require some validation to see if provided level exists in available configuration
		$this->activeConfiguration = $this->settings['level'][$this->level];
	}

	/**
	 * Show single item details per publication configuration.
	 *
	 * @return void
	 */
	public function showAction() {
		$this->view->assign('configuration', $this->activeConfiguration);
		// @TODO what if NULL? doesn't the template break?
		$this->view->assign(
			'item',
			$this->typeProcessor->processShow(
				$this->activeConfiguration, $this->import
			)
		);
	}

	/**
	 * List items per publication configuration.
	 *
	 * @return void
	 */
	public function listAction() {
		$this->view->assign('configuration', $this->activeConfiguration);
		$this->view->assign(
			'items',
			$this->typeProcessor->processList(
				$this->activeConfiguration, $this->import
			)
		);
	}

	/**
	 * Run multiple publish-configurations and/or custom TS elements as a single cohesive content element + overarching template.
	 *
	 * @return void
	 */
	public function complexAction() {
		$this->view->assign(
			'contentSections',
			$this->typeProcessor->processTypeRecursion(
				$this->activeConfiguration, $this->import
			)
		);
	}

}
