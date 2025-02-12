<?php

namespace Innologi\Decosdata\ViewHelpers;

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
use Innologi\Decosdata\Exception\PaginationError;
use Innologi\Decosdata\Service\PaginateService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

// @LOW ___use \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface ?
/**
 * PageBrowser ViewHelper
 *
 * Produces a pagebrowser of choice, while the actual pagination is already done
 * prior to building the view. Because of this, a widget would only add unnecessary
 * overhead, as we can't really use its characteristics anyway. A partial template
 * can still be assigned to a ViewHelper.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PageBrowserViewHelper extends AbstractViewHelper
{
    // @LOW _argumentsToBeExcludedFromQueryString="{0:'tx_decosdata_publish[page]'}" on page 1 links?
    // @TODO ___what about link titles?
    /**
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * @var PaginateService
     */
    protected $paginateService;

    /**
     * @var array
     */
    protected $pageLabelMap;

    public function injectPaginateService(PaginateService $paginateService): void
    {
        $this->paginateService = $paginateService;
    }

    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('partial', 'string', 'Dedicated partial template override.', false, 'ViewHelpers/PageBrowser');
        $this->registerArgument('startScalingAtPageCount', 'integer', 'Scaling starts if this many pages are present. 0 disables scaling', false, '21');
        $this->registerArgument('scalingFormat', 'string', 'Scaling reduces size of a pagebrowser if it has way too many pages. {BeforeScaled}|{BeforeCurrent}|{AfterCurrent}|{AfterScaled}.', false, '1|4|4|1');
        $this->registerArgument('renderAbove', 'boolean', 'Renders pagebrowser above content.', false, true);
        $this->registerArgument('renderBelow', 'boolean', 'Renders pagebrowser below content.', false, true);
        $this->registerArgument('includeResultCountAbove', 'boolean', 'Includes total amount of results with pagebrowser rendered above content.', false, true);
        $this->registerArgument('includeResultCountBelow', 'boolean', 'Includes total amount of results with pagebrowser rendered below content.', false, false);
        $this->registerArgument('renderAlways', 'boolean', 'Renders pagebrowser even if there is only one page.', false, false);
        $this->registerArgument('pageLimit', 'integer', 'Artificial page limit. Only affects displayed pages, as pagination values have already been applied to Query.');
        $this->registerArgument('xhrEnabled', 'boolean', 'XHR mode enabled?', false, false);
        // for now, this only has use for xhr
        $this->registerArgument('more', 'string', 'Either the next page number or an XHR URL', false, '');
        $this->registerArgument('autoload', 'boolean', 'Immediately autoload next results', false, false);
        $this->registerArgument('xhrTarget', 'string', 'xhr target class', false, 'section');
    }

    /**
     * Render Page Browser
     *
     * @return string
     */
    public function render()
    {
        // render pagebrowser only if active or if renderAlways was set
        if (!($this->paginateService->isActive() || $this->arguments['renderAlways'])) {
            return $this->renderChildren();
        }

        $section = null;
        $configuration = array_merge([
            'renderAbove' => $this->arguments['renderAbove'],
            'renderBelow' => $this->arguments['renderBelow'],
            //@LOW won't $escapeChildren = FALSE work?
            // requires the use of format.raw VH, which costs us ~1.6 ms on average, but keeps us
            // from using a marker like ###CONTENT### with str_replace, which can easily be fooled
            'content' => $this->renderChildren(),
        ], $this->buildResultCountConfiguration());
        if ($this->arguments['xhrEnabled']) {
            $configuration['xhrUri'] = $this->arguments['more'];
            $configuration['xhrAutoload'] = $this->arguments['autoload'] ? 1 : 0;
            $configuration['xhrTarget'] = $this->arguments['xhrTarget'];
            $configuration['nextPageArgs'] = [
                'page' => $this->paginateService->getCurrentPage() + 1,
            ];
            $section = 'xhr';
        } else {
            $configuration['pageBrowser'] = $this->buildPageBrowserConfiguration();
        }

        // render a specific partial that exists for this sole purpose
        return $this->viewHelperVariableContainer->getView()->renderPartial(
            $this->arguments['partial'],
            $section,
            $configuration,
        );
    }

    /**
     * Build pagebrowser template configuration arguments
     *
     * @return array
     * @throws \Innologi\Decosdata\Exception\PaginationError
     */
    protected function buildPageBrowserConfiguration()
    {
        // these are valid regardless if pageService is ready
        $this->pageLabelMap = $this->paginateService->getPageLabelMap();
        $currentPage = $this->paginateService->getCurrentPage();
        $pageCount = $this->paginateService->getPageCount();

        // artificially override page count if the argument was set lower than actual page count
        if (isset($this->arguments['pageLimit']) && $this->arguments['pageLimit'] < $pageCount) {
            $pageCount = $this->arguments['pageLimit'];
        }

        $configuration = [
            'currentPage' => $this->createPageElement($currentPage),
            'pages' => [],
        ];

        // determine whether scaling is applied
        if ($this->arguments['startScalingAtPageCount'] && $pageCount >= $this->arguments['startScalingAtPageCount']) {
            $scaleParts = explode('|', (string) $this->arguments['scalingFormat']);
            if (count($scaleParts) !== 4) {
                throw new PaginationError(1449155248, [
                    'ViewHelper.scalingFormat', $this->arguments['scalingFormat'], '1|4|4|1',
                ]);
            }
        } else {
            // if scaling is not applied, set values that will result in a pagebrowser without scaling
            $scaleParts = [
                0 => 0,
                1 => $currentPage - 1,
                2 => $pageCount - $currentPage,
                3 => 0,
            ];
        }

        // if there are previous pages
        if ($currentPage > 1) {
            $configuration['previousPage'] = [
                'number' => $currentPage - 1,
                'label' => 'previous',
            ];

            // beforeCurrent starts at page 1, unless beforeScaled is applied
            $beforeCurrentStart = 1;
            // only apply beforeScaled if beforeScaled- and beforeCurrent-pages aren't connected
            if ($currentPage > ($scaleParts[0] + $scaleParts[1] + 1)) {
                $configuration['pages']['beforeScaled'] = [];
                for ($i = 1; $i <= $scaleParts[0]; $i++) {
                    $configuration['pages']['beforeScaled'][] = $this->createPageElement($i);
                }
                $beforeCurrentStart = $currentPage - $scaleParts[1];
            }
            $configuration['pages']['beforeCurrent'] = [];
            for ($i = $beforeCurrentStart; $i < $currentPage; $i++) {
                $configuration['pages']['beforeCurrent'][] = $this->createPageElement($i);
            }
        }
        // if there are more pages following
        if ($currentPage < $pageCount) {
            $configuration['nextPage'] = [
                'number' => $currentPage + 1,
                'label' => 'next',
            ];

            // afterCurrent stops at last page, unless afterScaled is applied
            $afterCurrentStop = $pageCount;
            // only apply afterScaled if afterCurrent- and afterScaled pages aren't connected
            if (($pageCount - $currentPage) > ($scaleParts[2] + $scaleParts[3] + 1)) {
                $configuration['pages']['afterScaled'] = [];
                for ($i = $pageCount - ($scaleParts[3] - 1); $i <= $pageCount; $i++) {
                    $configuration['pages']['afterScaled'][] = $this->createPageElement($i);
                }
                $afterCurrentStop = $currentPage + $scaleParts[2];
            }
            $configuration['pages']['afterCurrent'] = [];
            for ($i = $currentPage + 1; $i <= $afterCurrentStop; $i++) {
                $configuration['pages']['afterCurrent'][] = $this->createPageElement($i);
            }
        }

        return $configuration;
    }

    /**
     * Builds resultcount template configuration
     *
     * @return array
     */
    protected function buildResultCountConfiguration()
    {
        $resultCountConfiguration = null;
        $resultCount = $this->paginateService->getResultCount();
        if ($resultCount !== null) {
            $resultCountConfiguration = [
                'includeResultCountAbove' => $this->arguments['includeResultCountAbove'],
                'includeResultCountBelow' => $this->arguments['includeResultCountBelow'],
                'resultCount' => $resultCount,
            ];
        } else {
            $resultCountConfiguration = [
                'includeResultCountAbove' => false,
                'includeResultCountBelow' => false,
                'resultCount' => null,
            ];
        }
        return $resultCountConfiguration;
    }

    /**
     * Creates a page element from a page number
     *
     * @param integer $pageNumber
     * @return array
     */
    protected function createPageElement($pageNumber)
    {
        return [
            'number' => $pageNumber,
            'label' => $this->pageLabelMap[$pageNumber] ?? $pageNumber,
        ];
    }
}
