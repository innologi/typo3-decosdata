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
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Info\Controller\InfoModuleController;


class Module
{

    /**
     * @var DocumentTemplate
     */
    protected $doc;

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
     *
     * @var integer
     */
    protected $searchLevel = 0;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     *
     * @var string
     */
    protected $buttons = '';

    /**
     *
     * @var string
     */
    protected $content = '';

    /**
     *
     * @var integer
     */
    protected $count = 0;

    /**
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     */
    public function init($pObj)
    {
        $this->pObj = $pObj;
        $this->getLanguageService()->includeLLFile('EXT:decosdata/Resources/Private/Language/locallang_mod.xlf');
        $this->getPageRenderer()->addInlineLanguageLabelFile('EXT:decosdata/Resources/Private/Language/locallang_mod.xlf');
        #$this->id = (int) GeneralUtility::_GP('id');
    }

    /**
     * Main, called from parent object
     *
     * @return string Module content
     */
    public function main()
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
        $flush = GeneralUtility::_GP('flushRoutingSlugs');
        if ($flush !== null) {
            $languageService = $this->getLanguageService();
            $this->flushRoutingSlugs();
            $this->messages[] = GeneralUtility::makeInstance(
                FlashMessage::class,
                sprintf($languageService->getLL('routing.flush_success'), $this->count),
                $languageService->getLL('routing.flush_success.title'),
                FlashMessage::OK
            );
            $this->count = 0;
        }
        $this->render();

        if (!empty($this->messages)) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            foreach ($this->messages as $message) {
                $defaultFlashMessageQueue->enqueue($message);
            }
        }

        #$pageTile = '';
        #if ($this->id) {
        #    $pageRecord = BackendUtility::getRecord('pages', $this->id);
        #    $pageTile = '<h1>' . htmlspecialchars(BackendUtility::getRecordTitle('pages', $pageRecord)) . '</h1>';
        #}

        return '<div id="decosdata-modfunc">' . $this->createTabs() . '</div>';
    }

    /**
     * Create tabs to split the report and the checkLink functions
     *
     * @return string
     */
    protected function createTabs()
    {
        $menuItems = [
            0 => [
                'label' => $this->getLanguageService()->getLL('tab.routing'),
                'content' => $this->flush()
            ],
        ];

        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        return $moduleTemplate->getDynamicTabMenu($menuItems, 'decosdata');
    }

    /**
     * Initializes the Module
     */
    protected function initialize()
    {
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:decosdata/Resources/Private/Templates/mod_template.html');

        #$this->pageRecord = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        #if ($this->id && is_array($this->pageRecord) || !$this->id && $this->getBackendUser()->isAdmin()) {
        // only accessible to admins NOT in workspace
        $this->isAccessibleForCurrentUser = $this->getBackendUser()->isAdmin() && $this->getBackendUser()->workspace === 0;
        #}

        $pageRenderer = $this->getPageRenderer();
        #$pageRenderer->addCssFile('EXT:decosdata/Resources/Public/Css/backend.css', 'stylesheet', 'screen');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Decosdata/Module');

        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }

    /**
     * Renders the content of the module
     */
    protected function render()
    {
    	$languageService = $this->getLanguageService();
        if ($this->isAccessibleForCurrentUser) {
            $this->buttons = '<p>' . $languageService->getLL('routing.missing_buttons') . '</p>'
                . '<input type="submit" class="btn btn-default t3js-update-button" name="flushRoutingSlugs" id="flushRoutingSlugs" value="'
                . htmlspecialchars(sprintf($languageService->getLL('routing.flush_label'), $this->count))
                . '" data-warning-message="'
                . htmlspecialchars($languageService->getLL('routing.flush_warning'))
                . '" data-notification-message="'
                . htmlspecialchars($languageService->getLL('routing.flush_notification'))
                . '"' . ($this->count===0 ? ' disabled="disabled"' : '')
                . '/>';
        } else {
            $this->messages[] = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->getLL('no_access'),
                $languageService->getLL('no_access.title'),
                FlashMessage::ERROR
            );
        }
    }

    /**
     * Flushes the rendered content to the browser
     *
     * @return string
     */
    protected function flush()
    {
        return $this->doc->moduleBody(
            [], #$this->pageRecord,
            $this->getDocHeaderButtons(),
            $this->getTemplateMarkers()
        );
    }

    /**
     * Builds the selector for the level of pages to search
     *
     * @return string
     */
    protected function getLevelSelector()
    {
        $languageService = $this->getLanguageService();
        // Build level selector
        $options = [];
        $availableOptions = [
            0 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
            1 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
            2 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
            3 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
            4 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
            999 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi')
        ];
        foreach ($availableOptions as $optionValue => $optionLabel) {
            $options[] = '<option value="' . $optionValue . '"' . ($optionValue === (int)$this->searchLevel ? ' selected="selected"' : '') . '>' . htmlspecialchars($optionLabel) . '</option>';
        }
        return '<select name="search_levels" class="form-control">' . implode('', $options) . '</select>';
    }

    /**
     * Generates an array of page uids from current pageUid.
     * List does include pageUid itself.
     *
     * @param int $currentPageUid
     * @return array
     */
    protected function getPageList(int $currentPageUid): array
    {
        $pageList = $this->linkAnalyzer->extGetTreeList(
            $currentPageUid,
            $this->searchLevel,
            0,
            $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW),
            $this->modTS['checkhidden']
        );
        // Always add the current page, because we are just displaying the results
        $pageList .= $currentPageUid;

        return GeneralUtility::intExplode(',', $pageList, true);
    }

    /**
     *
     * @param array $pageList Pages to check for routing slugs
     * @return Statement
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
                    $queryBuilder->createNamedParameter($pageList, Connection::PARAM_INT_ARRAY)
                )
            )
            ->orderBy('uid')
            ->execute();
    }

    /**
     *
     * @return int
     */
    protected function countRoutingSlugs(): int
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_decosdata_routing_slug')
            ->count('*', 'tx_decosdata_routing_slug', []);
    }

    /**
     *
     * @return int
     */
    protected function flushRoutingSlugs(): int
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_decosdata_routing_slug')
            ->truncate('tx_decosdata_routing_slug');
    }

    /**
     * Gets the buttons that shall be rendered in the docHeader
     *
     * @return array Available buttons for the docHeader
     */
    protected function getDocHeaderButtons()
    {
        return [
            'csh' => BackendUtility::cshItem('_MOD_web_func', ''),
            'shortcut' => $this->getShortcutButton(),
            'save' => ''
        ];
    }

    /**
     * Gets the button to set a new shortcut in the backend (if current user is allowed to).
     *
     * @return string HTML representation of the shortcut button
     */
    protected function getShortcutButton()
    {
        $result = '';
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $result = $this->doc->makeShortcutIcon('', 'function', 'web_info');
        }
        return $result;
    }

    /**
     * Gets the filled markers that are used in the HTML template
     * Reports tab
     *
     * @return array The filled marker array
     */
    protected function getTemplateMarkers()
    {
        $languageService = $this->getLanguageService();
        return [
            'TITLE' => $languageService->getLL('routing.title'),
            'DETAILS' => $languageService->getLL('routing.details'),
            'BUTTONS' => $this->buttons,
            'CONTENT' => $this->content,
        ];
    }

    /**
     * Called from InfoModuleController until deprecation removal in TYPO3 v10.0
     *
     * @return void
     */
    public function checkExtObj()
    {
        // do nothing
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
