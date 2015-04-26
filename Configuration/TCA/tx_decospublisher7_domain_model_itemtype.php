<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decospublisher7';
$table = 'tx_' . $extKey . '_domain_model_itemtype';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return array(
	'ctrl' => array(
		'title'	=> $ll,
		'label' => 'item_type',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY item_type ASC',
		// @TODO __replace icon
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden, item_type',
	),
	'types' => array(
		'0' => array('showitem' => 'hidden, item_type'),
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

		'item_type' => array(
			'exclude' => FALSE,
			'label' => $ll . '.item_type',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'required,uniqueInPid,nospace,trim,alphanum,upper',
				'placeholder' => $ll . '.item_type.placeholder'
			),
		),

	),
);
