<?php

namespace Innologi\Decosdata\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Domain\Repository\ItemRepository;
use Innologi\Decosdata\Exception\ConfigurationError;
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\Decosdata\Service\QueryBuilder\QueryBuilder;
use Innologi\TYPO3AssetProvider\ProviderServiceInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Item controller
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TypeProcessorService implements SingletonInterface
{
    /**
     * @var ItemRepository
     */
    protected $itemRepository;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var RenderOptionService
     */
    protected $optionService;
    // @LOW don't inject this one
    /**
     * @var ProviderServiceInterface
     */
    protected $assetProviderService;

    /**
     * @var \TYPO3\CMS\Core\TypoScript\TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \Innologi\Decosdata\Service\Paginate\PaginateServiceFactory
     */
    protected $paginatorFactory;

    protected Request $request;

    protected UriBuilder $uriBuilder;

    public function injectItemRepository(ItemRepository $itemRepository): void
    {
        $this->itemRepository = $itemRepository;
    }

    public function injectQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function injectOptionService(RenderOptionService $optionService): void
    {
        $this->optionService = $optionService;
    }

    public function injectAssetProviderService(ProviderServiceInterface $assetProviderService): void
    {
        $this->assetProviderService = $assetProviderService;
    }

    public function injectUriBuilder(UriBuilder $uriBuilder): void
    {
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Returns Paginator Factory
     *
     * @return \Innologi\Decosdata\Service\Paginate\PaginateServiceFactory
     */
    protected function getPaginatorFactory()
    {
        if ($this->paginatorFactory === null) {
            $this->paginatorFactory = GeneralUtility::makeInstance(\Innologi\Decosdata\Service\Paginate\PaginateServiceFactory::class);
        }
        return $this->paginatorFactory;
    }


    public function getTypoScriptService()
    {
        if ($this->typoScriptService === null) {
            $this->typoScriptService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class);
        }
        return $this->typoScriptService;
    }

    public function getConfigurationManager()
    {
        if ($this->configurationManager === null) {
            $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        }
        return $this->configurationManager;
    }

    /**
     * Sets Extbase request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
        $this->uriBuilder->setRequest($request);
        $this->optionService->setRequest($request);
    }


    public function processTypeRecursion(array &$configuration, array $import, $index = 0)
    {
        $content = [];

        // level types are required here
        if (!isset($configuration['_typoScriptNodeValue'])) {
            throw new ConfigurationError(1509719824, ['Missing TYPE definition for level configuration']);
        }
        $type = $configuration['_typoScriptNodeValue'];
        if (isset($type[0]) && $type[0] === '_') {
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
                    $lType = strtolower(substr((string) $type, 1));
                    $formattedType = ucfirst($lType);
                    $method = 'process' . $formattedType;
                    if (!method_exists($this, $method)) {
                        // @TODO throw exception
                    }
                    $content[$index] = [
                        'partial' => $configuration['partial'] ?? 'Item/' . $formattedType,
                        'type' => $lType,
                        'configuration' => $configuration,
                        'data' => $this->{$method}($configuration, $import, $index),
                        'paging' => isset($configuration['paginate']) && is_array($configuration['paginate'])
                            ? $this->processPaging($configuration['paginate'], $index)
                            : null,
                    ];
            }
        } else {
            // consider this a normal TYPO3 ContentObject
            $content[$index] = [
                'partial' => $configuration['partial'] ?? 'ContentObject',
                'type' => strtolower((string) $type),
                'configuration' => $configuration,
                // @extensionScannerIgnoreLine false positive
                'data' => $this->getConfigurationManager()->getContentObject()->cObjGetSingle(
                    $type,
                    $this->getTypoScriptService()->convertPlainArrayToTypoScriptArray($configuration),
                ),
            ];
        }

        return $content;
    }

    public function processList(array &$configuration, array $import, $section = 0)
    {
        # @TODO remove debugging!
        $items = $this->processRenderOptions(
            $this->itemRepository->findWithStatement(
                ($statement = $this->queryBuilder->buildListQuery(
                    $configuration,
                    $import,
                )->createStatement()),
            ),
            $configuration,
            $section,
        );
        $test = $statement->getProcessedQuery();

        return $items;
    }

    public function processShow(array &$configuration, array $import, $section = 0, $restrictItemId = null)
    {
        if ($restrictItemId !== null) {
            if (!isset($configuration['queryOptions'])) {
                $configuration['queryOptions'] = [];
            }
            // restrict results to this specific item id
            $configuration['queryOptions'][] = [
                'option' => 'RestrictByItem',
                'args' => [
                    'id' => (string) $restrictItemId,
                ],
            ];
        }

        $items = $this->processRenderOptions(
            $this->itemRepository->findWithStatement(
                $this->queryBuilder->buildListQuery(
                    $configuration,
                    $import,
                )->setLimit(
                    1,
                )->createStatement(),
            ),
            $configuration,
            $section,
        );

        return $items[0] ?? null;
    }

    public function processGallery(array &$configuration, array $import, $section = 0)
    {
        return $this->processList($configuration, $import, $section);
    }

    public function processMedia(array &$configuration, array $import, $section = 0)
    {
        return $this->processShow($configuration, $import, $section);
    }

    public function processContent(array &$configuration, array $import, $section, $restrictItemId, $contentId)
    {
        // @LOW throw exception if not apiMode?
        $item = $this->processShow($configuration, $import, $section, $restrictItemId);

        if (!\array_key_exists('content' . $contentId, $item)) {
            throw new ConfigurationError(1530603857, 'Contentfield ' . $contentId . ' is not properly configured, therefore cannot be retrieved');
        }

        return [
            'type' => 'content',
            // @LOW it would be nice if type content returns the given content field as array,
            // it would mean cleaner, smaller JSON output, and having an actual use for itemTemplate in the XHR JS,
            // however, RenderOptions currently returns a TagBuilder/TagInterface which is designed to
            // render as string always, and I don't want to mess with that piece of clean code,
            // so would RenderOptions need to change by returning something else? Or would paginateService
            // need to be API-aware (which is something I already may do) and add a divider instead of
            // XhrContainer/Elements tags, which we could explode on here?
            'data' => $item['content' . $contentId],
            'paging' => $item['paging' . $contentId] ?? null,
        ];
    }

    public function processSearch(array &$configuration)
    {
        /** @var \Innologi\Decosdata\Service\SearchService $searchService */
        $searchService = GeneralUtility::makeInstance(\Innologi\Decosdata\Service\SearchService::class);
        $data = [
            'search' => ($searchService->isActive() ? $searchService->getSearchString() : ''),
            'searchArguments' => [],
        ];

        if (isset($configuration['level'])) {
            $data['searchArguments']['targetLevel'] = (int) $configuration['level'];
        }
        // @TODO might want to make this consistent with the paging way of xhr.enable
        if (isset($configuration['xhr']) && is_array($configuration['xhr'])) {
            // @LOW if no source given, you should actually throw an exception, otherwise we'll get some other exception that doesn't explain context
            $data['section'] = (int) $configuration['xhr']['source'] ?? 0;
            $settings = $this->getConfigurationManager()->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            );
            $data['xhrUri'] = $this->uriBuilder->reset()
                ->setTargetPageType($settings['api']['type'])
                ->uriFor('search', $data['searchArguments'] + [
                    'section' => $data['section'],
                ]);

            if ($this->request->getFormat() === 'html') {
                // provide assets as configured per feature
                $this->assetProviderService->provideAssets('decosdata', 'Item', 'xhr');
            }
        }

        return $data;
    }

    /**
     * Iterates through items to process RenderOptions.
     *
     * @param integer $section
     * @return array
     */
    protected function processRenderOptions(array $items, array $configuration, $section = 0)
    {
        foreach ($items as &$item) {
            foreach ($configuration['contentField'] as $index => $config) {
                if (!isset($config['renderOptions'])) {
                    continue;
                }

                //if (!isset($item['content' . $index])) {
                // @TODO throw exception?
                // we ARE supporting this in configurations that do not offer a contentfield config
                // but does it have any value to not simply add the field by default?
                // also, what does this mean for content fields that result in NULL value from DB? (this happens)
                // should they be identifiable as a NULL value? Why yes? If not, does it matter?
                //
                // .. TagBuilder no longer supports NULL $content and with good reason
                // so for now we just added ?? '' below as a quick fix
                //}

                $paginator = null;
                if (isset($config['paginate']) && is_array($config['paginate'])) {
                    $paginator = $this->getPaginatorFactory()
                        ->get([$section, $item['id'], $index])
                        ->initialize(
                            $config['paginate'],
                            $this->request,
                        );
                }

                $item['content' . $index] = $this->optionService->processOptions(
                    $config['renderOptions'],
                    $item['content' . $index] ?? '',
                    $index,
                    $item,
                    $paginator,
                )->render();

                if ($paginator !== null) {
                    $item['paging' . $index] = $paginator->getPaginationData();
                }
            }
        }
        return $items;
    }

    // @TODO clean up this method, its xhr component is setup inefficiently
    // also this is really starting to get the opposite from transparent
    // feels hacky because of the whole forcing single thing
    protected function processPaging(array $paging, $section = 0)
    {
        // @TODO technically, this should be contained in paginateService, but it doesn't have the controllerContext yet
        if (isset($paging['xhr']['enable']) && (bool) $paging['xhr']['enable'] && isset($paging['more']) && $paging['more'] !== false) {
            $paging['autoload'] = (bool) ($paging['xhr']['autoload'] ?? false);
            $paging['xhr'] = true;
            $arguments = [
                'page' => $paging['more'],
                'section' => $section,
            ];
            if ($this->request->hasArgument('search')) {
                $arguments['search'] = $this->request->getArgument('search');
            }
            $settings = $this->getConfigurationManager()->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            );
            $paging['more'] = $this->uriBuilder->reset()
                ->setAddQueryString(true)
                ->setTargetPageType($settings['api']['type'])
                ->uriFor(
                    // overrule current action on queryString in case of forward
                    //$this->request->getControllerActionName(),
                    // @LOW is there ever any reason this would not be ok?
                    'single',
                    $arguments,
                );

            if ($this->request->getFormat() === 'html') {
                // provide assets as configured per feature
                $this->assetProviderService->provideAssets('decosdata', 'Item', 'xhr');
            }
        } else {
            $paging['xhr'] = false;
        }
        return $paging;
    }
}
