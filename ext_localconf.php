<?php
defined('TYPO3_MODE') or die();

$ll = 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:';

// plugin configuration
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Innologi.' . $_EXTKEY,
	'Publish',
	array(
		'Item' => 'list',
	),
	// non-cacheable actions
	array()
);

// add scheduler tasks
$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['Innologi\\Decosdata\\Task\\ImporterTask'] = array(
	'extension'			=> $_EXTKEY,
	'title'				=> $ll . 'task_importer.title',
	'description'		=> $ll . 'task_importer.description',
	'additionalFields'	=> 'Innologi\\Decosdata\\Task\\ImporterAdditionalFieldProvider'
);
