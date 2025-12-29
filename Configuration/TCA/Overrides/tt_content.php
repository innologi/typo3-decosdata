<?php

defined('TYPO3') or die();

// add plugins
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Decosdata',
    'Publish',
    'Decos Data: Publish',
);

// add the flexform
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:decosdata/Configuration/FlexForms/flexform_publish.xml',
    'decosdata_publish',
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;Configuration,pi_flexform,', 'decosdata_publish', 'after:subheader');
