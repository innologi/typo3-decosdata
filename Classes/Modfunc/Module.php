<?php

namespace Innologi\Decosdata\Modfunc;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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

use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Info\Controller\InfoModuleController;

class Module
{
    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Information about the current page record
     *
     * @var array
     */
    protected $pageRecord = [];

    /**
     * Information, if the module is accessible for the current user or not
     *
     * @var bool
     */
    protected $isAccessibleForCurrentUser = false;

    /**
     * TSconfig of the current module
     *
     * @var array
     */
    protected $modTS = [];

    /**
     * @var integer
     */
    protected $searchLevel = 0;

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     * @var integer
     */
    protected $count = 0;


    public function init(InfoModuleController $pObj): void
    {
        $this->pObj = $pObj;
        #$this->id = (int) GeneralUtility::_GP('id');
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->view = $this->createView('InfoModule');

        $this->getLanguageService()->includeLLFile('EXT:decosdata/Resources/Private/Language/locallang_mod.xlf');
        // @extensionScannerIgnoreLine false positive
        $this->getPageRenderer()->addInlineLanguageLabelFile('EXT:decosdata/Resources/Private/Language/locallang_mod.xlf');
    }

    protected function createView(string $templateName): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:decosdata/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:decosdata/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:decosdata/Resources/Private/Templates/Backend']);
        $view->setTemplate($templateName);
        #$view->assign('pageId', $this->id);
        return $view;
    }

    public function main(): string
    {
        #if (isset($this->id)) {
        #    $this->modTS = BackendUtility::getPagesTSconfig($this->id)['mod.']['decosdata.'] ?? [];
        #}

        // get searchLevel (number of levels of pages to check / show results)
        #$this->searchLevel = GeneralUtility::_GP('search_levels');
        #if ($this->searchLevel === null) {
        #    $this->searchLevel = $this->pObj->MOD_SETTINGS['searchlevel'];
        #} else {
        #    $this->pObj->MOD_SETTINGS['searchlevel'] = $this->searchLevel;
        #}
        // save settings
        #$this->getBackendUser()->pushModuleData('web_info', $this->pObj->MOD_SETTINGS);

        $this->initialize();

        $this->count = $this->countRoutingSlugs();
        $flush = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['flushRoutingSlugs'] ?? null;
        if ($flush !== null) {
            $this->flushRoutingSlugs();
            $this->moduleTemplate->addFlashMessage(
                sprintf($this->getLanguageService()->getLL('routing.flush_success'), $this->count),
                $this->getLanguageService()->getLL('routing.flush_success.title'),
                FlashMessage::OK,
            );
            $this->count = 0;
        }

        $pageTitle = !empty($this->pageRecord) ? BackendUtility::getRecordTitle('pages', $this->pageRecord) : '';
        $this->view->assign('title', $pageTitle);
        $this->view->assign('content', $this->renderContent());
        return $this->view->render();
    }

    protected function initialize(): void
    {
        #$this->pageRecord = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        #if (($this->id && is_array($this->pageRecord)) || (!$this->id && $this->getBackendUser()->isAdmin())) {
        // only accessible to admins NOT in workspace
        $this->isAccessibleForCurrentUser = $this->getBackendUser()->isAdmin() && $this->getBackendUser()->workspace === 0;
        #}

        // @extensionScannerIgnoreLine false positive
        $pageRenderer = $this->getPageRenderer();
        #$pageRenderer->addCssFile('EXT:decosdata/Resources/Public/Css/backend.css', 'stylesheet', 'screen');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Decosdata/Module');
    }

    protected function renderContent(): string
    {
        if (!$this->isAccessibleForCurrentUser) {
            $this->moduleTemplate->addFlashMessage(
                $this->getLanguageService()->getLL('no.access'),
                $this->getLanguageService()->getLL('no.access.title'),
                FlashMessage::ERROR,
            );
            return '';
        }

        return $this->createTabs();
    }

    protected function createTabs(): string
    {
        $routingTabView = $this->createView('RoutingTab');
        $routingTabView->assign('count', $this->count);
        $menuItems = [
            0 => [
                'label' => $this->getLanguageService()->getLL('tab.routing'),
                'content' => $routingTabView->render(),
            ],
        ];

        return $this->moduleTemplate->getDynamicTabMenu($menuItems, 'decosdata');
    }

    /**
     * @param array $pageList Pages to check for routing slugs
     */
    protected function getRoutingSlugs(array $pageList): Statement
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_decosdata_routing_slug');
        return $queryBuilder
            ->select('*')
            ->from('tx_decosdata_routing_slug')
            ->where(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($pageList, Connection::PARAM_INT_ARRAY),
                ),
            )
            ->orderBy('uid')
            ->executeQuery();
    }

    protected function countRoutingSlugs(): int
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_decosdata_routing_slug')
            ->count('*', 'tx_decosdata_routing_slug', []);
    }

    protected function flushRoutingSlugs(): int
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_decosdata_routing_slug')
            ->truncate('tx_decosdata_routing_slug');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
