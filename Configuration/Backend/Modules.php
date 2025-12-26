<?php

use Innologi\Decosdata\Controller\RoutingSlugController;

/**
 * Definitions for modules provided by EXT:decosdata
 */
return [
    'web_decosdata' => [
        'parent' => 'tools',
        //'position' => ['after' => '*'], // does the opposite?
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/web/decosdata',
        'iconIdentifier' => 'module-decosdata',
        'labels' => 'LLL:EXT:decosdata/Resources/Private/Language/locallang_mod.xlf',
        'routes' => [
            '_default' => [
                'target' => RoutingSlugController::class . '::handleRequest',
            ],
        ],
    ],
];
