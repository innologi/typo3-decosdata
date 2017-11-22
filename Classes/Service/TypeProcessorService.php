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
	 * @var \TYPO3\CMS\Core\TypoScript\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObjectRenderer;

	public function getTypoScriptService() {
		if ($this->typoScriptService === NULL) {
			$this->typoScriptService = $this->objectManager->get(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class);
		}
		return $this->typoScriptService;
	}

	public function getContentObjectRenderer() {
		if ($this->contentObjectRenderer === NULL) {
			$this->contentObjectRenderer = $this->objectManager->get(
				\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class
			)->getContentObject();
		}
		return $this->contentObjectRenderer;
	}



	public function processTypeRecursion(array $configuration, array $import) {
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
					foreach ($configuration as $conf) {
						$content = array_merge($content, $this->processTypeRecursion($conf, $import));
					}
					break;
				default:
					$lType = strtolower(substr($type, 1));
					$formattedType = ucfirst($lType);
					$method = 'process' . $formattedType;
					if (!method_exists($this, $method)) {
						// @TODO throw exception
					}
					$content[] = [
						'partial' => $configuration['partial'] ?? 'Item/' . $formattedType,
						'type' => $lType,
						'configuration' => $configuration,
						'data' => $this->{$method}($configuration, $import)
					];
			}
		} else {
			// consider this a normal TYPO3 ContentObject
			/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer */
			$contentObjectRenderer = $GLOBALS['TSFE']->cObj;
			$content[] = [
				'partial' => $configuration['partial'] ?? 'ContentObject',
				'type' => strtolower($type),
				'configuration' => $configuration,
				'data' => $this->getContentObjectRenderer()->cObjGetSingle(
					$type, $this->getTypoScriptService()->convertPlainArrayToTypoScriptArray($configuration)
				)
			];
		}

		return $content;
	}

	public function processList(array $configuration, array $import) {
		# @TODO remove debugging!
		$items = $this->itemRepository->findWithStatement(
			($statement = $this->queryBuilder->buildListQuery(
				$configuration, $import
			)->createStatement())
		);
		$test = $statement->getProcessedQuery();
		return $items;
	}

	public function processShow(array $configuration, array $import) {
		$items = $this->itemRepository->findWithStatement(
			$this->queryBuilder->buildListQuery(
				$configuration, $import
			)->setLimit(
				1
			)->createStatement()
		);

		return $items[0] ?? NULL;
	}

	public function processGallery(array $configuration, array $import) {
		return $this->processList($configuration, $import);
	}

	public function processMedia(array $configuration, array $import) {
		return $this->processShow($configuration, $import);
	}

	public function processSearch(array $configuration) {
		/** @var \Innologi\Decosdata\Service\SearchService $searchService */
		$searchService = $this->objectManager->get(\Innologi\Decosdata\Service\SearchService::class);
		return [
			'targetLevel' => $configuration['level'] ?? NULL,
			'search' => $searchService->isActive() ? $searchService->getSearchString() : ''
		];
	}

}
