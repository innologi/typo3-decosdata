<?php
namespace Innologi\Decosdata\Service;
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
use TYPO3\CMS\Core\SingletonInterface;
use Innologi\Decosdata\Exception\BreadcrumbError;
/**
 * Breadcrumb Service
 *
 * Provides the crumbpath information for any configuration.
 * Holds several key variables to be displayed afterwards.
 *
 * Works in tandem with the CrumbPathViewHelper.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class BreadcrumbService implements SingletonInterface {
	// @LOW _should we validate the crumb levels with those from the actual configuration?
	// @LOW consider that the @injects aren't always necessary, so you might want to work with getMethods instead

	/**
	 * @var \Innologi\Decosdata\Service\ParameterService
	 * @inject
	 */
	protected $parameterService;

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
	 * @var integer
	 */
	protected $currentLevel = 1;

	/**
	 * @var array
	 */
	protected $crumbLabelMap = [];

	/**
	 * @var boolean
	 */
	protected $active = FALSE;

	/**
	 * Returns current level
	 *
	 * @return integer
	 */
	public function getCurrentLevel() {
		return $this->currentLevel;
	}

	/**
	 * Returns crumb mapping
	 *
	 * @return array
	 */
	public function getCrumbLabelMap() {
		return $this->crumbLabelMap;
	}

	/**
	 * Confirms whether the service was successfully configured and active.
	 *
	 * Note that this also returns FALSE when misconfigured, so not ready !== error
	 *
	 * @return boolean
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * Initialize Breadcrumbs (if not locked)
	 *
	 * @param array $configuration
	 * @param array $import
	 * @return boolean
	 */
	public function configureBreadcrumb(array $configuration, array $import) {
		$this->initializeConfiguration();
		$this->configureDefault($configuration, $import);
		return TRUE;
	}

	/**
	 * Initialize configuration
	 *
	 * @return void
	 */
	protected function initializeConfiguration() {
		$this->currentLevel = $this->parameterService->getParameterNormalized('level');

		// invalidate any previous configuration, just in case
		$this->active = FALSE;
	}

	/**
	 * Configures default breadcrumb
	 *
	 * @param array $configuration
	 * @param array $import
	 * @return void
	 * @throws \Innologi\Decosdata\Exception\BreadcrumbError
	 */
	protected function configureDefault(array $configuration, array $import) {
		try {
			foreach ($configuration as $level => $config) {
				// don't process past current level
				if ($level > $this->currentLevel) {
					break;
				}
				$crumb = '';

				// @TODO don't you want to offer this option to general content as well? if so, we need to move this into something else
				if (isset($config['value'])) {
					$crumb = $config['value'];
				} elseif ($config['contentField']) {
					// treat as normal content
					$items = $this->itemRepository->findWithStatement(
						$this->queryBuilder->buildListQuery($config, $import)->setLimit(1)->createStatement()
					);
					// 0 results
					if (empty($items)) {
						throw new BreadcrumbError(1509383208, [$level, 'no query-results']);
					}
					// somehow erroneous query or NULL value
					if (!isset($items[0]['content1'])) {
						throw new BreadcrumbError(1509383681, [$level, 'missing or empty content field 1']);
					}
					$crumb = $items[0]['content1'];
				}

				$this->crumbLabelMap[$level] = $crumb;
			}
		} catch (\Innologi\Decosdata\Exception\Exception $e) {
			throw new BreadcrumbError($e->getCode(), NULL, $e->getMessage());
		}

		// if the current level isn't available
		if (!isset($this->crumbLabelMap[$this->currentLevel])) {
			throw new BreadcrumbError(1509382055, [$this->currentLevel, 'missing in configuration']);
		}
		$this->active = TRUE;
	}

}
