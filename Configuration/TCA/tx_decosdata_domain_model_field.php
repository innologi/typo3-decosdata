<?php
defined('TYPO3_MODE') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decosdata';
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
		'enablecolumns' => array(),
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY field_name ASC',
		// @TODO __replace icon
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif',
	),
	'interface' => array(
		'showRecordFieldList' => 'field_name',
	),
	'types' => array(
		'0' => array('showitem' => 'field_name'),
	),
	'palettes' => array(),
	'columns' => array(

		'field_name' => array(
			'exclude' => FALSE,
			'label' => $ll . '.field_name',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'required,uniqueInPid,nospace,trim,alphanum_x,upper',
				'placeholder' => $ll . '.field_name.placeholder'
			),
		),

	),
);