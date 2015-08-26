<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$ll = 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:';

// add scheduler tasks
$TYPO3_CONF_VARS['SC_OPTIONS']['scheduler']['tasks']['Innologi\\Decosdata\\Task\\ImporterTask'] = array(
	'extension'			=> $_EXTKEY,
	'title'				=> $ll . 'task_importer.title',
	'description'		=> $ll . 'task_importer.description',
	'additionalFields'	=> 'Innologi\\Decosdata\\Task\\ImporterAdditionalFieldProvider'
);
