<?php

namespace Innologi\Decosdata\Controller;

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

class RoutingSlugController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly ConnectionPool $connectionPool,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        $count = $this->countRoutingSlugs();
        $flush = $request->getParsedBody()['flushRoutingSlugs'] ?? null;
        if ($flush !== null) {
            $this->flushRoutingSlugs();
            // @todo why are we still doing this if we also use the js backend notifier?
            $moduleTemplate->addFlashMessage(
                sprintf($this->getLanguageService()->sl('LLL:EXT:decosdata/Resources/Private/Language/locallang_mod.xlf:routing.flush_success'), $count),
                $this->getLanguageService()->sl('LLL:EXT:decosdata/Resources/Private/Language/locallang_mod.xlf:routing.flush_success.title'),
                ContextualFeedbackSeverity::OK,
            );
            $count = 0;
        }

        $moduleTemplate->assign('count', $count);
        return $moduleTemplate->renderResponse('Backend/RoutingTab');
    }

    /**
     * @param array $pageList Pages to check for routing slugs
     */
    protected function getRoutingSlugs(array $pageList): Statement
    {
        $queryBuilder = $this->connectionPool
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
        return $this->connectionPool
            ->getConnectionForTable('tx_decosdata_routing_slug')
            ->count('*', 'tx_decosdata_routing_slug', []);
    }

    protected function flushRoutingSlugs(): int
    {
        return $this->connectionPool
            ->getConnectionForTable('tx_decosdata_routing_slug')
            ->truncate('tx_decosdata_routing_slug');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
