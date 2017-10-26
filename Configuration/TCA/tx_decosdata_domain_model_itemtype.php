<?php
defined('TYPO3_MODE') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_itemtype';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
	'ctrl' => [
		'title'	=> $ll,
		'label' => 'item_type',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => [],
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY item_type ASC',
		// @TODO __replace icon
		'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif'
	],
	'interface' => [
		'showRecordFieldList' => 'item_type',
	],
	'types' => [
		'0' => ['showitem' => 'item_type'],
	],
	'palettes' => [],
	'columns' => [

		'item_type' => [
			'exclude' => FALSE,
			'label' => $ll . '.item_type',
			'config' => [
				'type' => 'input',
				'size' => 30,
				'eval' => 'required,uniqueInPid,nospace,trim,alphanum,upper',
				'placeholder' => $ll . '.item_type.placeholder'
			],
		],

	],
];
