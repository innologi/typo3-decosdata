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
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Innologi\Decosdata\Task\ImporterTask::class] = array(
	'extension'        => $_EXTKEY,
	'title'            => $ll . 'task_importer.title',
	'description'      => $ll . 'task_importer.description',
	'additionalFields' => \Innologi\Decosdata\Task\ImporterAdditionalFieldProvider::class
);

// Register CLI keys
$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY . ':migrate'] = array(
	'EXT:' . $_EXTKEY . '/cli/migrate_cli_dispatch.php',
	// @LOW ___change to relevant user?
	'_CLI_lowlevel'
);
