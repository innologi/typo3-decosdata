<?php
defined('TYPO3_MODE') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_itemblob';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
	'ctrl' => [
		'title'	=> $ll,
		// @LOW ___ for more descriptive TCA labels, check out http://docs.typo3.org/typo3cms/TCAReference/Reference/Ctrl/Index.html#label-userfunc
		'label' => 'sequence',
		'label_alt' => 'item',
		'label_alt_force' => TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		],
		'dividers2tabs' => 1,
		'hideTable' => TRUE,
		'hideAtCopy' => TRUE,
		'default_sortby' => 'ORDER BY uid DESC',
		// the table is non-searchable
		//'searchFields' => '',
		'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif'
	],

	'types' => [
		'0' => [
			'showitem' => 'hidden, item_key, sequence, file,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'
		],
	],
	'palettes' => [],
	'columns' => [

		'hidden' => [
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
			'config' => [
				'type' => 'check',
				'default' => 1
			],
		],
		'starttime' => [
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
			'config' => [
				'type' => 'input',
				'renderType' => 'inputDateTime',
				'eval' => 'datetime',
				'default' => 0,
				'behaviour' => [
					'allowLanguageSynchronization' => TRUE,
				]
			],
		],
		'endtime' => [
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
			'config' => [
				'type' => 'input',
				'renderType' => 'inputDateTime',
				'eval' => 'datetime',
				'default' => 0,
				'range' => [
					'upper' => mktime(0, 0, 0, 1, 1, 2038)
				],
				'behaviour' => [
					'allowLanguageSynchronization' => TRUE,
				]
			],
		],

		'item_key' => [
			'exclude' => FALSE,
			'label' => $ll . '.item_key',
			'config' => [
				'type' => 'input',
				'size' => 40,
				'max' => 32,
				'eval' => 'required,uniqueInPid,trim,nospace,alphanum,upper'
			],
		],
		'sequence' => [
			'exclude' => FALSE,
			'label' => $ll . '.sequence',
			'config' => [
				'type' => 'input',
				'size' => 40,
				'max' => 32,
				'eval' => 'required,int'
			],
		],
		'file' => [
			'exclude' => FALSE,
			'label' => $ll . '.file',
			'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
				'file',
				[
					'maxitems' => 1,
					'foreign_match_fields' => [
						'fieldname' => 'file',
						'tablenames' => $table,
						'table_local' => 'sys_file',
					],
				]
			),
		],
		'item' => [
			'config' => [
				'type' => 'passthrough',
			],
		],

	],
];
