<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decospublisher7';
$table = 'tx_' . $extKey . '_domain_model_field';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return array(
	'ctrl' => array(
		'title'	=> $ll,
		'label' => 'field_name',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY field_name ASC',
		// @TODO __replace icon
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif',
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden, field_name',
	),
	'types' => array(
		'0' => array('showitem' => 'hidden, field_name'),
	),
	'palettes' => array(),
	'columns' => array(

		'hidden' => array(
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
			),
		),

		'field_name' => array(
			'exclude' => FALSE,
			'label' => $ll . '.field_name',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'required,uniqueInPid,nospace,trim,alphanum,upper',
				'placeholder' => $ll . '.field_name.placeholder'
			),
		),

	),
);