<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// add scheduler tasks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Innologi\\Decospublisher7\\Task\\ImporterTask'] = array(
	'extension'			=> $_EXTKEY,
	// @TODO ___llang
	'title'				=> 'Importer', #'LLL:EXT:' . $_EXTKEY . '/tasks/locallang.xml:updatetask.name',
	'description'		=> 'Importer Descr', #'LLL:EXT:' . $_EXTKEY . '/tasks/locallang.xml:updatetask.description',
	'additionalFields'	=> ''
);
