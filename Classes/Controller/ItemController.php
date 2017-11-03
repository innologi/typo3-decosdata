<?php
namespace Innologi\Decosdata\Controller;
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
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Innologi\Decosdata\Exception\ConfigurationError;
/**
 * Item controller
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemController extends ActionController {

	/**
	 * @var \Innologi\Decosdata\Domain\Repository\ItemRepository
	 * @inject
	 */
	protected $itemRepository;

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
	 * @var integer
	 */
	protected $level = 1;

	/**
	 * @var array
	 */
	protected $activeConfiguration;

	/**
	 * {@inheritDoc}
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
	 */
	protected function initializeAction() {
		// initializes and validates request parameters shared by all actions
		if ($this->request->hasArgument('level')) {
			// set current level
			$this->level = (int) $this->request->getArgument('level');
			if ($this->request->hasArgument('_' . $this->level)) {
				// @TODO what to do with this one?
				$levelParameter = $this->request->getArgument('_' . $this->level);
			}
		}
		// @LOW ___will probably require some validation to see if provided level exists in available configuration

		// initialize breadcrumb
		if (isset($this->settings['breadcrumb']) && is_array($this->settings['breadcrumb'])) {
			// @LOW this being optional, means I probably shouldn't inject it
			$this->breadcrumbService->configureBreadcrumb($this->settings['breadcrumb'], $this->settings['import']);
		}
	}

	/**
	 * Initialize show action
	 *
	 * @return void
	 */
	protected function initializeShowAction() {
		$this->activeConfiguration = $this->settings['level'][$this->level];
	}

	/**
	 * Initialize list action
	 *
	 * @return void
	 */
	protected function initializeListAction() {
		// @LOW _consider that said check could throw an exception, and that we could then apply an override somewhere that catches it to produce a 404? (or just generate a flash message, which I think we've done in another extbase ext)
		if ($this->request->hasArgument('page') && isset($this->settings['level'][$this->level]['paginate'])) {
			// a valid page-parameter will set current page in configuration
			$this->settings['level'][$this->level]['paginate']['currentPage'] = (int) $this->request->getArgument('page');
		}
		$this->activeConfiguration = $this->settings['level'][$this->level];
	}

	/**
	 * Initialize advanced action
	 *
	 * @return void
	 * @throws ConfigurationError
	 */
	protected function initializeAdvancedAction() {
		if (!isset($this->settings['level'][$this->level]['_typoScriptNodeValue'])) {
			throw new ConfigurationError(1509719824, ['Missing TypoScript ContentObject configuration on level ' . $this->level]);
		}

		/** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
		$configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
		// content object configurations require the original TS
		$originalTypoScript = $configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
		);
		$this->activeConfiguration = $originalTypoScript['plugin.']['tx_decosdata.']['settings.']['level.'][$this->level . '.'];
	}

	/**
	 * Show single item details per publication configuration.
	 *
	 * @return void
	 */
	public function showAction() {
		$items = $this->itemRepository->findWithStatement(
			$this->queryBuilder->buildListQuery(
				$this->activeConfiguration, $this->settings['import']
			)->setLimit(1)->createStatement()
		);

		$this->view->assign('configuration', $this->activeConfiguration);
		$this->view->assign('item', $items[0] ?? NULL);
	}

	/**
	 * List items per publication configuration.
	 *
	 * @return void
	 */
	public function listAction() {
		$items = $this->itemRepository->findWithStatement(
			($statement = $this->queryBuilder->buildListQuery(
				$this->activeConfiguration, $this->settings['import']
			)->createStatement())
		);

		$this->view->assign('configuration', $this->activeConfiguration);
		$this->view->assign('items', $items);
		// @TODO ___remove
		$this->view->assign('query', $statement->getProcessedQuery());
	}

	/**
	 * Run multiple publish-configurations and/or custom TS elements as a single cohesive content element.
	 *
	 * @return void
	 */
	public function advancedAction() {
		// if successfully configured, lock its state, as you generally only want 1 state if plugins are nested
		!$this->breadcrumbService->isActive() or $this->breadcrumbService->lock(TRUE);

		/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer */
		$contentObjectRenderer = $GLOBALS['TSFE']->cObj;
		$contentType = $this->settings['level'][$this->level]['_typoScriptNodeValue'];
		$content = $contentObjectRenderer->cObjGetSingle($contentType, $this->activeConfiguration);

		// unlock its state in case of other plugin content elements
		$this->breadcrumbService->lock(FALSE);

		$this->view->assign('content', $content);
	}

}
