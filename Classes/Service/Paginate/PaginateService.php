<?php
namespace Innologi\Decosdata\Service\Paginate;
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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use Innologi\Decosdata\Exception\NotInitialized;
use Innologi\Decosdata\Exception\PaginationError;
/**
 * Paginate Service
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PaginateService {
	// @LOW we should consider using an instance of this in the original paginateService as well

	/**
	 * @var integer
	 */
	protected $total;

	/**
	 * @var integer
	 */
	protected $limit;

	/**
	 * @var integer
	 */
	protected $page = 1;

	/**
	 * @var integer
	 */
	protected $pageLimit;

	/**
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * @var integer
	 */
	protected $index = 0;

	/**
	 * @var array
	 */
	protected $xhrConfiguration = [];

	/**
	 * @var boolean
	 */
	protected $__initialized = FALSE;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \Innologi\TagBuilder\TagFactory
	 * @inject
	 */
	protected $tagFactory;

	/**
	 * @var \Innologi\Decosdata\Service\ParameterService
	 * @inject
	 */
	protected $parameterService;

	/**
	 * @var ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * @var array
	 */
	protected $callbackArgs = [];

	/**
	 * @var array
	 */
	protected $addedSectionParameters = [];

	/**
	 * Returns Configuration Manager
	 *
	 * @return \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected function getConfigurationManager() {
		if ($this->configurationManager === NULL) {
			$this->configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
		}
		return $this->configurationManager;
	}

	/**
	 * Initialize the pagination service with all the necessary parameters
	 *
	 * If you intend to use this Service by letting it iterate through specific code,
	 * be sure to pass along a callback.
	 *
	 * @param array $configuration
	 * @param ControllerContext $controllerContext
	 * @param callable $iterateCallback
	 * @param array $callbackArgs
	 * @return $this
	 */
	public function initialize(array $configuration, ControllerContext $controllerContext, callable $iterateCallback = NULL, array $callbackArgs = []) {
		$this->controllerContext = $controllerContext;
		$this->callback = $iterateCallback;
		$this->callbackArgs = $callbackArgs;
		$this->parameterService->initializeByRequest($controllerContext->getRequest());

		// note that these are all not based on an actual total
		$this->pageLimit = (int) ($configuration['pageLimit'] ?? 100);
		$this->limit = (int) ($configuration['perPageLimit'] ?? 100);
		$this->total = $this->pageLimit * $this->limit;
		$currentPage = $this->parameterService->getParameterNormalized('cpage');
		$this->page = $currentPage > $this->pageLimit ? $this->pageLimit : $currentPage;
		$this->offset = $this->limit * ($this->page-1);

		#if (isset($configuration['xhr']) && is_array($configuration['xhr'])) {
		#	$this->xhrConfiguration = $configuration['xhr'];
		#}

		$this->__initialized = TRUE;
		return $this;
	}

	/**
	 * Adds a section-parameter-segment for next URI
	 *
	 * @param integer $sectionParameter
	 * @return $this
	 */
	public function addSectionParameter($sectionParameter) {
		$this->addedSectionParameters[] = $sectionParameter;
		return $this;
	}

	/**
	 * Set total of number of elements.
	 *
	 * Setting a total will re-evaluate affected parameters.
	 * Although optional if you don't have the total available,
	 * you generally always want to do this before execute()
	 * for the most accurate results.
	 *
	 * @param integer $total
	 * @return $this
	 * @throws NotInitialized
	 */
	public function setTotal($total) {
		if (!$this->__initialized) {
			throw new NotInitialized(1528815836, [self::class]);
		}

		$this->total = (int) $total;
		if ($this->total < $this->limit) {
			$this->limit = $this->total;
		}

		$pages = (int) ceil($this->total / $this->limit);
		if ($pages < $this->pageLimit) {
			$this->pageLimit = $pages;
		}

		if ($this->pageLimit < $this->page) {
			$this->page = $this->pageLimit;
		}
		$this->offset = $this->limit * ($this->page-1);

		return $this;
	}

	/**
	 * Execute pagination and generate more parameter if applicable.
	 *
	 * @throws NotInitialized
	 * @return array
	 */
	public function execute() {
		if (!$this->__initialized) {
			throw new NotInitialized(1528815842, [self::class]);
		}

		$result = $this->iterate();
		if ($this->hasNext()) {
			$result[] = $this->getNext();
		}
		return $result;
	}

	/**
	 * Iterates through pagination-callback
	 *
	 * @throws NotInitialized
	 * @throws PaginationError
	 * @return array
	 */
	public function iterate() {
		if (!$this->__initialized) {
			throw new NotInitialized(1528815881, [self::class]);
		}
		if ($this->callback === NULL) {
			throw new PaginationError(1528815938, [],
				'PaginationService cannot iterate if no callback wass given through initialization.'
			);
		}

		$result = [];
		for ($i = 0, $this->index = $this->offset; $i < $this->limit && $this->index < $this->total; $i++, $this->index++) {
			$result[] = \call_user_func_array($this->callback, $this->callbackArgs);
		}
		return $result;
	}

	/**
	 * Returns whether there is a next page.
	 *
	 * @throws NotInitialized
	 * @return boolean
	 */
	public function hasNext() {
		if (!$this->__initialized) {
			throw new NotInitialized(1528816720, [self::class]);
		}

		return $this->page < $this->pageLimit;
	}

	/**
	 * Returns next page link
	 *
	 * @throws NotInitialized
	 * @return \Innologi\TagBuilder\Tag|NULL
	 */
	public function getNext() {
		if (!$this->__initialized) {
			throw new NotInitialized(1528816720, [self::class]);
		}

		return $this->hasNext() ? $this->tagFactory->createTag(
			'a',
			['href' => $this->buildNextUri()],
			$this->tagFactory->createTagContent('more')
		) : NULL;
	}

	/**
	 * Builds next page URI
	 *
	 * @return string
	 */
	protected function buildNextUri() {
		$arguments = [ 'cpage' => $this->page+1 ];
		#if (isset($this->xhrConfiguration['source'])) {
		#	$arguments['section'] = join('|',
		#		\array_merge([(int)$this->xhrConfiguration['source']], $this->addedSectionParameters)
		#	);
		#}

		$settings = $this->getConfigurationManager()->getConfiguration(
			ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
		);
		return $this->controllerContext->getUriBuilder()->reset()
			->setCreateAbsoluteUri(TRUE)
			->setAddQueryString(TRUE)
			#->setTargetPageType($settings['api']['type'])
			->uriFor(
				$this->controllerContext->getRequest()->getControllerActionName(),
				#'single',
				$arguments
			);

		// @TODO where to do this?
		#if ($this->controllerContext->getRequest()->getFormat() === 'html') {
			// provide assets as configured per feature
		#	$this->assetProviderService->provideAssets('decosdata', 'Item', 'xhr');
		#}
	}

	/**
	 * Returns pagination offset
	 *
	 * @return integer
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Returns per-page element limit
	 *
	 * @return integer
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Returns index of current iteration
	 *
	 * @return integer
	 */
	public function getIterateIndex() {
		return $this->index;
	}
}
