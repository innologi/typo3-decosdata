<?php

namespace Innologi\Decosdata\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Exception\EarlyResponseThrowable;
use Innologi\Decosdata\Service\BreadcrumbService;
use Innologi\Decosdata\Service\ParameterService;
use Innologi\Decosdata\Service\QueryBuilder\QueryBuilder;
use Innologi\Decosdata\Service\TypeProcessorService;
use Innologi\Decosdata\View\Item\MultiJson;
use Innologi\Decosdata\View\Item\SingleJson;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;

/**
 * Item controller
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemController extends ActionController
{
    /**
     * @var TypeProcessorService
     */
    protected $typeProcessor;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var BreadcrumbService
     */
    protected $breadcrumbService;

    /**
     * @var ParameterService
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
     * @var boolean
     */
    protected $apiMode = false;

    public function injectTypeProcessor(TypeProcessorService $typeProcessor): void
    {
        $this->typeProcessor = $typeProcessor;
    }

    public function injectQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function injectBreadcrumbService(BreadcrumbService $breadcrumbService): void
    {
        $this->breadcrumbService = $breadcrumbService;
    }

    public function injectParameterService(ParameterService $parameterService): void
    {
        $this->parameterService = $parameterService;
    }

    /**
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
     */
    protected function initializeAction()
    {
        $this->parameterService->initializeByRequest($this->request);
        $this->level = $this->parameterService->getParameterNormalized('level');

        // detect and set apiMode defaults
        $this->apiMode = (int) $GLOBALS['TSFE']->getPageArguments()->getPageType() === (int) $this->settings['api']['type'];
        if ($this->apiMode && !$this->parameterService->hasParameter('format')) {
            // default API format
            $this->request = $this->request->withFormat($this->settings['api']['defaultFormat']);
        }
        $this->typeProcessor->setRequest($this->request);

        // @LOW cache?
        // check override TS
        if (isset($this->settings['override']['publish'][0])) {
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
            $this->import = GeneralUtility::intExplode(',', $this->settings['override']['import'], true);
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
            // @TODO search action is already USER_INT. Why am I converting the follow-up GET requests to USER_INT again?
            // @TODO also, can't we get rid of POST here? I remember how we got to using it, but let's face it: a search
            // request should be GET, or even a POST-redirect-GET, so we should find a way to do so consistently.

            $contentObject = $this->request->getAttribute('currentContentObject');
            if ($contentObject->getUserObjectType() === \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER) {
                $contentObject->convertToUserIntObject();
                // will recreate the object, so we have to stop the request here
                throw new EarlyResponseThrowable(new Response());
            }

            /** @var \Innologi\Decosdata\Service\SearchService $searchService */
            $this->searchService = GeneralUtility::makeInstance(\Innologi\Decosdata\Service\SearchService::class);
            if (!$this->searchService->isActive()) {
                $this->searchService->enableSearch(
                    $this->parameterService->getParameterRaw('search'),
                );
            }
        }
    }

    /**
     * Set View for multiAction()
     */
    protected function initializeMultiAction()
    {
        if ($this->request->getFormat() === 'json') {
            // in TYPO3 v10, ViewResolver will only resolve whatever is the default view
            $this->defaultViewObjectName = MultiJson::class;
        }
    }

    /**
     * Set View for singleAction()
     */
    protected function initializeSingleAction()
    {
        if ($this->request->getFormat() === 'json') {
            // in TYPO3 v10, ViewResolver will only resolve whatever is the default view
            $this->defaultViewObjectName = SingleJson::class;
        }
    }

    /**
     * Run multiple publish-configurations and/or custom TS elements as a single cohesive content element + overarching template.
     */
    public function multiAction(): ResponseInterface
    {
        $this->view->assign('level', $this->level);
        $this->view->assign(
            'contentSections',
            $this->typeProcessor->processTypeRecursion(
                $this->activeConfiguration,
                $this->import,
            ),
        );
        return $this->view instanceof JsonView ? $this->jsonResponse() : $this->htmlResponse();
    }

    /**
     * Run a single out of multiple publish-configurations and/or custom TS elements.
     *
     * @param integer $section
     * @param integer $item
     * @param integer $content
     */
    public function singleAction($section, $item = null, $content = null): ResponseInterface
    {
        $data = null;
        $this->activeConfiguration = $this->activeConfiguration[$section] ?? [];

        // @LOW maybe support non-xhr modes as well?
        // enable xhr mode on pagination as well
        if ($this->apiMode && isset($this->activeConfiguration['paginate']) && is_array($this->activeConfiguration['paginate'])) {
            // @LOW maybe if the paging service has its own apimode-detection, it can set the appropriate parameters by itself
            if (!(isset($this->activeConfiguration['paginate']['xhr']['enable']) && (bool) $this->activeConfiguration['paginate']['xhr']['enable'])) {
                $this->activeConfiguration['paginate']['xhr']['enable'] = true;
            }
        }

        if ($item !== null) {
            if ($content !== null) {
                // section -> item -> content
                $data = $this->typeProcessor->processContent(
                    $this->activeConfiguration,
                    $this->import,
                    $section,
                    $item,
                    $content,
                );
            } else {
                // section -> item
                $data = $this->typeProcessor->processShow(
                    $this->activeConfiguration,
                    $this->import,
                    $section,
                    $item,
                );
            }
        } else {
            // section
            $data = current(
                $this->typeProcessor->processTypeRecursion(
                    $this->activeConfiguration,
                    $this->import,
                    $section,
                ),
            );
            // we only really need the content fields, other query-added fields will only pad the JSON size
            if ($this->view instanceof SingleJson) {
                $this->view->addContentFieldsToConfiguration(\count($this->activeConfiguration['contentField']));
            }
        }

        $this->view->assign('level', $this->level);
        $this->view->assign('section', $data);
        $this->view->assign('sectionIndex', $section);
        return $this->view instanceof JsonView ? $this->jsonResponse() : $this->htmlResponse();
    }

    /**
     * Search request validation and redirect.
     * Search can actually be done in any action, but POST search needs to be done through this one.
     * Param is set as a requirement, otherwise we did not instantiate searchService and the whole
     * action would be useless to go through.
     *
     * @param string $search
     */
    public function searchAction($search): ResponseInterface
    {
        $arguments = [];
        $action = 'multi';

        // we're only passing along our search parameter if it is a sensible one
        if ($this->searchService->isActive()) {
            $arguments['search'] = $this->parameterService->encodeParameter(
                $this->searchService->getSearchString(),
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

        // redirect to default action
        return $this->redirectOrForward($action, null, null, $arguments);
    }


    // @TODO once you support these directly via flexform with default configs, review the templating stuff
    /**
     * Show single item details per publication configuration.
     */
    public function singleShowAction(): ResponseInterface
    {
        $this->view->assign('level', $this->level);
        $this->view->assign('configuration', $this->activeConfiguration);
        $this->view->assign(
            'item',
            $this->typeProcessor->processShow(
                $this->activeConfiguration,
                $this->import,
            ),
        );
        return $this->view instanceof JsonView ? $this->jsonResponse() : $this->htmlResponse();
    }

    /**
     * List items per publication configuration.
     */
    public function singleListAction(): ResponseInterface
    {
        $this->view->assign('level', $this->level);
        $this->view->assign('configuration', $this->activeConfiguration);
        $this->view->assign(
            'items',
            $this->typeProcessor->processList(
                $this->activeConfiguration,
                $this->import,
            ),
        );
        return $this->view instanceof JsonView ? $this->jsonResponse() : $this->htmlResponse();
    }

    /**
     * Redirect wrapper. Detects API-mode and switches to forward() instead
     * since API-mode is not compatible with redirects.
     *
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::redirect()
     */
    protected function redirectOrForward(...$args): ResponseInterface
    {
        if ($this->apiMode) {
            // @TODO why was it not compatible with redirects? I forgot if this is fixable,
            // but it's in the way of a solution to getting GET search results.
            $response = new ForwardResponse($args[0]);
            if (\is_string($args[1])) {
                $response = $response->withControllerName($args[1]);
            }
            if (\is_string($args[2])) {
                $response = $response->withExtensionName($args[2]);
            }
            if (\is_array($args[3])) {
                $response = $response->withArguments($args[3]);
            }
            return $response;
        } else {
            return $this->redirect(...$args);
        }
    }

    public function processRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return parent::processRequest($request);
        } catch (EarlyResponseThrowable $t) {
            return $t->getResponse();
        }
    }
}
