<?php
defined('TYPO3_MODE') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_profile';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

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
		),
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY title ASC',
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden, title, file, profile_key, profile_field',
	),
	'types' => array(
		'0' => array(
			'showitem' => 'hidden, title, file, profile_key,
				--div--;' . $ll . '.profile_field,profile_field'
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
					'maxitems' => 1
				),
				'xml'
			),
		),
		'profile_key' => array(
			'exclude' => FALSE,
			'label' => $ll . '.profile_key',
			'config' => array(
				'type' => 'input',
				'size' => 40,
				'max' => 32,
				'eval' => 'required,uniqueInPid,trim,nospace,alphanum,upper'
			),
		),
		'profile_field' => array(
			'exclude' => FALSE,
			'label' => $ll . '.profile_field',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_decosdata_domain_model_profilefield',
				'foreign_field' => 'profile',
				// this can easily be circumvented, see profilefield.field TCA for solution
				'foreign_unique' => 'field',
				// @LOW __since these are id's now, it seems I can't sort on field.field_name anymore
				'foreign_default_sortby' => 'tx_decosdata_domain_model_profilefield.field ASC',
				// override ctrl/label setting
				'foreign_label' => 'field',
				'foreign_selector' => 'field',
				// once language_code is supported, these are possible solutions
				/*'foreign_match_fields' => array(
					'language_code' => 'nl',
				),
				'filter => ..*/
				'maxitems' => 9999,
				'appearance' => array(
					'collapseAll' => TRUE,
					'levelLinksPosition' => 'top',
				),
				'behaviour' => array(
					'enableCascadingDelete' => TRUE,
				),
			),
		),
	),
);
