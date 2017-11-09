<?php
defined('TYPO3_MODE') or die();

// add plugins
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'Innologi.Decosdata',
	'Publish',
	'Decos Data: Publish'
);

// add the flexform
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
	'decosdata_publish',
	'FILE:EXT:decosdata/Configuration/FlexForms/flexform_publish.xml'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['decosdata_publish'] = 'pi_flexform';
// remove some unused fields
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['decosdata_publish'] = 'pages,recursive';