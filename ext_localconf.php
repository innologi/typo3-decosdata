<?php
defined('TYPO3_MODE') or die();

$ll = 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:';

// plugin configuration
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Innologi.' . $_EXTKEY,
	'Publish',
	[
		'Item' => 'multi,single,search'
	],
	// non-cacheable actions
	[
		'Item' => 'search'
	]
);

// add scheduler tasks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Innologi\Decosdata\Task\ImporterTask::class] = [
	'extension'        => $_EXTKEY,
	'title'            => $ll . 'task_importer.title',
	'description'      => $ll . 'task_importer.description',
	'additionalFields' => version_compare(TYPO3_version, '9.4', '<')
		? \Innologi\Decosdata\Task\CompatImporterAdditionalFieldProvider::class
		: \Innologi\Decosdata\Task\ImporterAdditionalFieldProvider::class
];

// add eid scripts
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_decosdata_download'] = 'EXT:decosdata/Classes/Eid/Download.php';
