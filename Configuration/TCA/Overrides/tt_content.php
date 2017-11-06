<?php
defined('TYPO3_MODE') or die();

// add publish-plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'Innologi.Decosdata',
	'Publish',
	'Publish Decos Data'
);
// add the flexform
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
	'decosdata_publish',
	'FILE:EXT:decosdata/Configuration/FlexForms/flexform_publish.xml'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['decosdata_publish'] = 'pi_flexform';