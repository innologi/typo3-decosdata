<?php
defined('TYPO3_MODE') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_import';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;
// this won't always be up to date because of caching, but that's ok
$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

return array(
	'ctrl' => array(
		'title'	=> $ll,
		'label' => 'title',
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
		'default_sortby' => 'ORDER BY title ASC',
		// @TODO __replace icon
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden, title, file, hash, forget_on_update',
	),
	'types' => array(
		'0' => array(
			'showitem' => 'hidden, title, file, hash, forget_on_update,
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

		'title' => array(
			'exclude' => FALSE,
			'label' => $ll . '.title',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'required,uniqueInPid,trim'
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
				),
				'xml'
			),
		),
		'hash' => array(
			'exclude' => TRUE,
			'label' => $ll . '.hash',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'eval' => 'trim'
			),
		),
		'forget_on_update' => array(
			'exclude' => TRUE,
			'label' => $ll . '.forget_on_update',
			'config' => array(
				'type' => 'check',
				'default' => 0
			)
		),

	),
);