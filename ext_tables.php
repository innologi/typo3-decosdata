<?php

defined('TYPO3') or die();

// Add module
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
    'web_info',
    \Innologi\Decosdata\Modfunc\Module::class,
    null,
    'Decosdata',
);
