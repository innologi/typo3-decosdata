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
	protected $pluginConfiguration;

	/**
	 * {@inheritDoc}
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
	 */
	public function initializeAction() {
		$this->initializePluginConfiguration();
		$this->initializeSharedArguments();
	}

	/**
	 * Initializes plugin configuration for use by any action method
	 *
	 * @return void
	 */
	protected function initializePluginConfiguration() {
		// @LOW _consider this: we translated querybuilder configuration from arrays to classes. would there be an advantage to doing the same for this? this isn't going to be adjustable like query configurations, but maybe there are other advantages?

		/** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
		$configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
		$frameworkConfiguration = $configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
		);

		$this->pluginConfiguration = $frameworkConfiguration['publish'];
	}

	/**
	 * Initializes and validates request parameters shared by all actions
	 *
	 * @return void
	 */
	protected function initializeSharedArguments() {
		if ($this->request->hasArgument('level')) {
			// set current level
			$this->level = (int) $this->request->getArgument('level');
			if ($this->request->hasArgument('_' . $this->level)) {
				$levelParameter = $this->request->getArgument('_' . $this->level);
			}
		}
		// @LOW ___will probably require some validation to see if provided level exists in available configuration
		// @LOW _consider that said check could throw an exception, and that we could then apply an override somewhere that catches it to produce a 404? (or just generate a flash message, which I think we've done in another extbase ext)
		if ($this->request->hasArgument('page')) {
			// a valid page-parameter will set current page in configuration
			$this->pluginConfiguration['level'][$this->level]['paginate']['currentPage'] = (int) $this->request->getArgument('page');
		}
	}

	/**
	 * List items per publication configuration.
	 *
	 * @return void
	 */
	public function listAction() {
		$activeConfiguration = $this->pluginConfiguration['level'][$this->level];
		$items = $this->itemRepository->findWithStatement(
			($statement = $this->queryBuilder->buildListQuery(
				$activeConfiguration, $this->pluginConfiguration['import']
			)->createStatement())
		);

		// initialize breadcrumb
		if (isset($this->pluginConfiguration['breadcrumb']) && is_array($this->pluginConfiguration['breadcrumb'])) {
			// @LOW this being optional, means I probably shouldn't inject it
			$this->breadcrumbService->configureBreadcrumb($this->pluginConfiguration['breadcrumb'], $this->pluginConfiguration['import']);
		}

		$this->view->assign('configuration', $activeConfiguration);
		$this->view->assign('extra', $this->pluginConfiguration['extra']);
		$this->view->assign('items', $items);
		// @TODO ___remove
		$this->view->assign('query', $statement->getProcessedQuery());
	}

	/**
	 * Show single item details per publication configuration.
	 *
	 * @return void
	 */
	public function showAction() {

	}
}
