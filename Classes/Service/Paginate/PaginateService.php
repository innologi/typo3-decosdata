<?php

namespace Innologi\Decosdata\Service\Paginate;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Exception\NotInitialized;
use Innologi\Decosdata\Exception\PaginationError;
use Innologi\Decosdata\Service\ParameterService;
use Innologi\TagBuilder\TagFactory;
use Innologi\TYPO3AssetProvider\ProviderServiceInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Paginate Service
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PaginateService
{
    // @LOW we should consider using an instance of this in the original paginateService as well

    /**
     * @var string
     */
    protected $id;

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
     * @var boolean
     */
    protected $xhrEnabled = false;

    /**
     * @var boolean
     */
    protected $xhrAutoload = false;

    /**
     * @var boolean
     */
    protected $__initialized = false;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var TagFactory
     */
    protected $tagFactory;

    /**
     * @var ParameterService
     */
    protected $parameterService;

    // @LOW don't inject this one
    /**
     * @var ProviderServiceInterface
     */
    protected $assetProviderService;

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
    protected $sectionParameters = [];

    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function injectTagFactory(TagFactory $tagFactory)
    {
        $this->tagFactory = $tagFactory;
    }

    public function injectParameterService(ParameterService $parameterService)
    {
        $this->parameterService = $parameterService;
    }

    public function injectAssetProviderService(ProviderServiceInterface $assetProviderService)
    {
        $this->assetProviderService = $assetProviderService;
    }

    /**
     * @param string $id
     */
    public function __construct($id, array $parameters)
    {
        $this->id = $id;
        $this->sectionParameters = $parameters;
    }

    /**
     * Returns Configuration Manager
     *
     * @return \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected function getConfigurationManager()
    {
        if ($this->configurationManager === null) {
            $this->configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
        }
        return $this->configurationManager;
    }

    /**
     * Initialize the pagination service with all the necessary parameters
     *
     * @return $this
     */
    public function initialize(array $configuration, ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
        $this->parameterService->initializeByRequest($controllerContext->getRequest());

        // note that these are all not based on an actual total
        $this->pageLimit = (int) ($configuration['pageLimit'] ?? 100);
        $this->limit = (int) ($configuration['perPageLimit'] ?? 100);
        $this->total = $this->pageLimit * $this->limit;
        $currentPage = $this->parameterService->getParameterNormalized('page' . $this->id);
        $this->page = $currentPage > $this->pageLimit ? $this->pageLimit : $currentPage;
        $this->offset = $this->limit * ($this->page - 1);

        $this->xhrEnabled = isset($configuration['xhr']['enable']) && (bool) $configuration['xhr']['enable'];
        if ($this->xhrEnabled) {
            $this->xhrAutoload = isset($configuration['xhr']['autoload']) && (bool) $configuration['xhr']['autoload'];
        }

        $this->__initialized = true;
        return $this;
    }

    /**
     * Sets callback for use by iterate() method
     *
     * @return $this
     */
    public function setCallback(callable $iterateCallback, array $args = [])
    {
        $this->callback = $iterateCallback;
        $this->callbackArgs = $args;
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
    public function setTotal($total)
    {
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
        $this->offset = $this->limit * ($this->page - 1);

        return $this;
    }

    /**
     * Execute all steps of pagination, add XHR tags if relevant,
     * and return as string.
     *
     * @param string $separator
     * @throws NotInitialized
     * @return string
     */
    public function execute($separator = '')
    {
        if (!$this->__initialized) {
            throw new NotInitialized(1528815842, [self::class]);
        }

        $result = $this->iterate();
        $result = $this->xhrEnabled ? $this->xhrWrapping($result, $separator) : join($separator, $result);

        return $result;
    }

    /**
     * Iterates through pagination-callback
     *
     * @throws NotInitialized
     * @throws PaginationError
     * @return array
     */
    public function iterate()
    {
        if (!$this->__initialized) {
            throw new NotInitialized(1528815881, [self::class]);
        }
        if ($this->callback === null) {
            throw new PaginationError(
                1528815938,
                [],
                'PaginationService cannot iterate if no callback was given.',
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
    protected function hasNext()
    {
        return $this->page < $this->pageLimit;
    }

    /**
     * Builds next page URI
     *
     * @param boolean $xhr
     * @return string
     */
    protected function buildNextUri($xhr = false)
    {
        $uriBuilder = $this->controllerContext->getUriBuilder()->reset()
            ->setAddQueryString(true);

        $arguments = [
            'page' . $this->id => $this->page + 1,
        ];
        if ($xhr && $this->xhrEnabled) {
            [$arguments['section'], $arguments['item'], $arguments['content']] = $this->sectionParameters;

            // @TODO where to do this?
            if ($this->controllerContext->getRequest()->getFormat() === 'html') {
                // provide assets as configured per feature
                $this->assetProviderService->provideAssets('decosdata', 'Item', 'xhr');
            }

            $settings = $this->getConfigurationManager()->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            );
            $uriBuilder->setTargetPageType($settings['api']['type']);
        }

        return $uriBuilder->uriFor(
            $xhr ? 'single' : $this->controllerContext->getRequest()->getControllerActionName(),
            $arguments,
        );
    }

    /**
     * Get pagination data
     *
     * @return array
     */
    public function getPaginationData()
    {
        if (!$this->__initialized) {
            throw new NotInitialized(1530544297, [self::class]);
        }
        return [
            'id' => $this->id,
            'pageLimit' => $this->pageLimit,
            'perPageLimit' => $this->limit,
            'page' => $this->page,
            'total' => $this->total,
            'xhr' => $this->xhrEnabled,
            'autoload' => $this->xhrAutoload,
            'more' => $this->hasNext() ? $this->buildNextUri($this->xhrEnabled) : false,
        ];
    }

    /**
     * Returns pagination offset
     *
     * @return integer
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Returns per-page element limit
     *
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Returns total result count
     *
     * @return integer
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Returns current page number
     *
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->page;
    }

    /**
     * Returns index of current iteration
     *
     * @return integer
     */
    public function getIterationIndex()
    {
        return $this->index;
    }

    /**
     * Is XHR enabled?
     *
     * @return boolean
     */
    public function isXhrEnabled()
    {
        return $this->xhrEnabled;
    }

    // @TODO I would prefer to replace this with something that does not change our original output this way,
    // AND allows us to more easily implement a JSON-aware solution that does not strictly return a string,
    // but otherwise, doc this!
    public function xhrWrapping(array $data, $separator = '')
    {
        foreach ($data as &$value) {
            $value = $this->xhrElement($value);
        }
        return $this->xhrContainer(\join($separator, $data));
    }
    protected function xhrElement($data)
    {
        return $this->wrapInTag($data, 'span', 'xhr-element');
    }
    protected function xhrContainer($data)
    {
        return $this->wrapInTag($data, 'span', 'xhr-container');
    }
    protected function wrapInTag($data, $element = 'span', $class = null)
    {
        return $this->tagFactory->createTag(
            $element,
            isset($class[0]) ? [
                'class' => $class,
            ] : [],
            $data instanceof \Innologi\TagBuilder\TagInterface ? $data : $this->tagFactory->createTagContent($data),
        );
    }
}
