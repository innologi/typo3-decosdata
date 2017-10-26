<?php
defined('TYPO3_MODE') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_itemblob';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;
// this won't always be up to date because of caching, but that's ok
$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

return array(
	'ctrl' => array(
		'title'	=> $ll,
		// @LOW ___ for more descriptive TCA labels, check out http://docs.typo3.org/typo3cms/TCAReference/Reference/Ctrl/Index.html#label-userfunc
		'label' => 'sequence',
		'label_alt' => 'item',
		'label_alt_force' => TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dividers2tabs' => 1,
		'hideTable' => TRUE,
		'hideAtCopy' => TRUE,
		'default_sortby' => 'ORDER BY uid DESC',
		// the table is non-searchable
		//'searchFields' => '',
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif'
	),

	'interface' => array(
		'showRecordFieldList' => 'hidden, item_key, sequence, file, item',
	),
	'types' => array(
		'0' => array(
			'showitem' => 'hidden, item_key, sequence, file,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'
		),
	),
	'palettes' => array(),
	'columns' => array(

		'hidden' => array(
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => 1
			),
		),
		'starttime' => array(
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'default' => 0,
				'range' => array(
					'lower' => $today
				),
			),
		),
		'endtime' => array(
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'default' => 0,
				'range' => array(
					'lower' => $today
				),
			),
		),

		'item_key' => array(
			'exclude' => FALSE,
			'label' => $ll . '.item_key',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'max' => 32,
				'eval' => 'required,uniqueInPid,trim,nospace,alphanum,upper'
			),
		),
		'sequence' => array(
			'exclude' => FALSE,
			'label' => $ll . '.sequence',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'max' => 32,
				'eval' => 'required,int'
			),
		),
		'file' => array(
			'exclude' => FALSE,
			'label' => $ll . '.file',
			'config' => ExtensionManagementUtility::getFileFieldTCAConfig(
				'file',
				array(
					'maxitems' => 1,
					'foreign_match_fields' => array(
						'fieldname' => 'file',
						'tablenames' => $table,
						'table_local' => 'sys_file',
					),
				)
			),
		),
		'item' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),

	),
);
