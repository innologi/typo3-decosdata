<?php
namespace Innologi\Decosdata\Service;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Exception\ConfigurationError;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
/**
 * Item controller
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TypeProcessorService implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

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
	 * @var \Innologi\Decosdata\Service\Option\RenderOptionService
	 * @inject
	 */
	protected $optionService;

	/**
	 * @var \TYPO3\CMS\Core\TypoScript\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @var ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var ControllerContext
	 */
	protected $controllerContext;


	public function getTypoScriptService() {
		if ($this->typoScriptService === NULL) {
			$this->typoScriptService = $this->objectManager->get(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class);
		}
		return $this->typoScriptService;
	}

	public function getConfigurationManager() {
		if ($this->configurationManager === NULL) {
			$this->configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
		}
		return $this->configurationManager;
	}

	/**
	 * Sets controller context
	 *
	 * @param ControllerContext $controllerContext
	 * @return void
	 */
	public function setControllerContext(ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
		$this->optionService->setControllerContext($controllerContext);
	}


	public function processTypeRecursion(array &$configuration, array $import, $index = 0) {
		$content = [];

		// level types are required here
		if (!isset($configuration['_typoScriptNodeValue'])) {
			throw new ConfigurationError(1509719824, ['Missing TYPE definition for level configuration']);
		}
		$type = $configuration['_typoScriptNodeValue'];
		if ($type[0] === '_') {
			// one of our plugin types
			unset($configuration['_typoScriptNodeValue']);
			switch ($type) {
				case '_COA':
					foreach ($configuration as $index => $conf) {
						// don't use array_merge, so we can keep our indexes
						$content = $content + $this->processTypeRecursion($conf, $import, $index);
					}
					break;
				default:
					$lType = strtolower(substr($type, 1));
					$formattedType = ucfirst($lType);
					$method = 'process' . $formattedType;
					if (!method_exists($this, $method)) {
						// @TODO throw exception
					}
					$content[$index] = [
						'partial' => $configuration['partial'] ?? 'Item/' . $formattedType,
						'type' => $lType,
						'configuration' => $configuration,
						'data' => $this->{$method}($configuration, $import)
					];
			}
		} else {
			// consider this a normal TYPO3 ContentObject
			$content[$index] = [
				'partial' => $configuration['partial'] ?? 'ContentObject',
				'type' => strtolower($type),
				'configuration' => $configuration,
				'data' => $this->getConfigurationManager()->getContentObject()->cObjGetSingle(
					$type, $this->getTypoScriptService()->convertPlainArrayToTypoScriptArray($configuration)
				)
			];
		}

		return $content;
	}

	public function processList(array &$configuration, array $import) {
		# @TODO remove debugging!
		$items = $this->processRenderOptions(
			$this->itemRepository->findWithStatement(
				($statement = $this->queryBuilder->buildListQuery(
					$configuration, $import
				)->createStatement())
			),
			$configuration
		);
		$test = $statement->getProcessedQuery();

		return $items;
	}

	public function processShow(array &$configuration, array $import) {
		$items = $this->processRenderOptions(
			$this->itemRepository->findWithStatement(
				$this->queryBuilder->buildListQuery(
					$configuration, $import
				)->setLimit(
					1
				)->createStatement()
			),
			$configuration
		);

		return $items[0] ?? NULL;
	}

	public function processGallery(array &$configuration, array $import) {
		return $this->processList($configuration, $import);
	}

	public function processMedia(array &$configuration, array $import) {
		return $this->processShow($configuration, $import);
	}

	public function processSearch(array &$configuration) {
		/** @var \Innologi\Decosdata\Service\SearchService $searchService */
		$searchService = $this->objectManager->get(\Innologi\Decosdata\Service\SearchService::class);
		$data = [
			'search' => ($searchService->isActive() ? $searchService->getSearchString() : ''),
			'targetLevel' => ($configuration['level'] ?? NULL)
		];

		if (isset($configuration['xhr']) && is_array($configuration['xhr'])) {
			$data['section'] = (int) $configuration['xhr']['source'] ?? 0;
			$settings = $this->getConfigurationManager()->getConfiguration(
				ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
			);
			$data['xhrUri'] = $this->controllerContext->getUriBuilder()->reset()
				->setCreateAbsoluteUri(TRUE)
				->setTargetPageType($settings['api']['type'])
				->uriFor('search', array_diff($data, ['search' => 1]));
		}

		return $data;
	}

	/**
	 * Iterates through items to process RenderOptions.
	 *
	 * @param array $items
	 * @param array $configuration
	 * @return array
	 */
	protected function processRenderOptions(array $items, array $configuration) {
		foreach ($items as &$item) {
			foreach ($configuration['contentField'] as $index => $config) {
				if (!isset($config['renderOptions'])) {
					continue;
				}
				if (!isset($item['content' . $index])) {
					// @TODO throw exception
				}
				$item['content' . $index] = $this->optionService->processOptions(
					$config['renderOptions'],
					$item['content' . $index],
					$index,
					$item
				)->render();
			}
		}
		return $items;
	}

}
