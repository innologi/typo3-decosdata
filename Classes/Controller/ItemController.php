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
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
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
	 * @var \Innologi\Decosdata\Service\SearchService
	 */
	protected $searchService;

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
		$this->parameterService->initializeByRequest($this->request);
		$this->level = $this->parameterService->getParameterNormalized('level');

		// @LOW cache?
		// check override TS
		if (isset(trim($this->settings['override']['publish'])[0])) {
			/** @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser $tsParser */
			$tsParser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
			$tsParser->parse($this->settings['override']['publish']);
			// completely replace original publicationsettings
			$this->settings['publish'] = $this->typeProcessor
				->getTypoScriptService()
				->convertTypoScriptArrayToPlainArray($tsParser->setup);
		}

		// set imports, flexform override -> publish ts -> []
		if (isset($this->settings['override']['import'][0])) {
			$this->import = GeneralUtility::intExplode(',', $this->settings['override']['import'], TRUE);
		} else {
			$this->import = $this->settings['publish']['import'] ?? [];
		}

		// initialize breadcrumb
		if (isset($this->settings['publish']['breadcrumb']) && is_array($this->settings['publish']['breadcrumb'])) {
			// @LOW this being optional, means I probably shouldn't inject it
			$this->breadcrumbService->configureBreadcrumb($this->settings['publish']['breadcrumb'], $this->import);
		}

		#if ($this->request->hasArgument('_' . $this->level)) {
			// @TODO what to do with this one?
		#	$levelParameter = $this->request->getArgument('_' . $this->level);
		#}

		// @LOW ___will probably require some validation to see if provided level exists in available configuration
		$this->activeConfiguration = $this->settings['publish']['level'][$this->level];

		// validate search
		if ($this->parameterService->hasParameter('search')) {
			// @TODO make sure caching is safe before disabling this. GET requests are cached correctly if I do this,
			// but I seem to be able to manually change the post request for it to contain different search terms
			// so I'm seeing quite an opportunity for automated cache pollution if I disable this without further changes.
			// The thing is, I'm caching the search plugin as part of default action, so I can't just put a CSRF token in there.
			// Can I somehow make my sections behave as USER_INT? But then it becomes yet another hacky mess..
			$contentObject = $this->configurationManager->getContentObject();
			if ($contentObject->getUserObjectType() === \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER) {
				$contentObject->convertToUserIntObject();
				// will recreate the object, so we have to stop the request here
				throw new StopActionException();
			}

			$search = $this->parameterService->getParameterRaw('search');
			/** @var \Innologi\Decosdata\Service\SearchService $searchService */
			$this->searchService = $this->objectManager->get(\Innologi\Decosdata\Service\SearchService::class);
			$this->searchService->enableSearch($search);
		}
	}

	/**
	 * Run multiple publish-configurations and/or custom TS elements as a single cohesive content element + overarching template.
	 *
	 * @return void
	 */
	public function multiAction() {
		$this->view->assign('level', $this->level);
		$this->view->assign(
			'contentSections',
			$this->typeProcessor->processTypeRecursion(
				$this->activeConfiguration, $this->import
			)
		);
	}

	/**
	 * Run a single out of multiple publish-configurations and/or custom TS elements.
	 *
	 * @param integer $section
	 * @return void
	 */
	public function singleAction($section) {
		$this->view->assign('level', $this->level);
		$this->view->assign(
			'section',
			current(
				$this->typeProcessor->processTypeRecursion(
					($this->activeConfiguration[$section] ?? []), $this->import
				)
			)
		);
	}

	/**
	 * Search request validation and redirect.
	 * Search can actually be done in any action, but POST search needs to be done through this one.
	 *
	 * @return void
	 */
	public function searchAction() {
		$arguments = [];
		$action = 'multi';

		// we're only passing along our search parameter if it is a sensible one
		if ($this->searchService->isActive()) {
			$arguments['search'] = $this->parameterService->encodeParameter(
				$this->searchService->getSearchString()
			);
		}

		// if we don't need to pass along the level parameter: don't
		if ($this->level > 1) {
			$arguments['level'] = $this->level;
		}

		// pass any section parameter
		if ($this->parameterService->hasParameter('section')) {
			$arguments['section'] = $this->parameterService->getParameterValidated('section');
			$action = 'single';
		}

		$this->redirect(NULL, NULL, NULL, $arguments);
	}



	/**
	 * Show single item details per publication configuration.
	 *
	 * @return void
	 */
	public function showAction() {
		$this->view->assign('level', $this->level);
		$this->view->assign('configuration', $this->activeConfiguration);
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
		$this->view->assign('level', $this->level);
		$this->view->assign('configuration', $this->activeConfiguration);
		$this->view->assign(
			'items',
			$this->typeProcessor->processList(
				$this->activeConfiguration, $this->import
			)
		);
	}
}
