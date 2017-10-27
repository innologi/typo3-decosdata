<?php
defined('TYPO3_MODE') or die();

// add static TS
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
	'decosdata',
	'Configuration/TypoScript',
	'Decos Data TS'
);