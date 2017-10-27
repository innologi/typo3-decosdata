<?php
defined('TYPO3_MODE') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_profilefield';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
	'ctrl' => [
		'title'	=> $ll,
		'label' => 'field',
		'label_alt' => 'profile',
		'label_alt_force' => TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden',
		],
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY profile DESC, field ASC',
		// only used in profile IRRE
		'hideTable' => TRUE,
		'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif'
	],
	'interface' => [
		'showRecordFieldList' => 'hidden, description, field, profile',
	],
	'types' => [
		'0' => [
			'showitem' => 'hidden, description, field, profile',
		],
	],
	'palettes' => [],
	'columns' => [

		'hidden' => [
			'exclude' => TRUE,
			'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
			'config' => [
				'type' => 'check',
			],
		],

		'description' => [
			'exclude' => FALSE,
			'label' => $ll . '.description',
			'config' => [
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,required'
			],
		],
		'field' => [
			'exclude' => FALSE,
			'label' => $ll . '.field',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tx_decosdata_domain_model_field',
				// the 2nd AND forces unique field per profile
				// note that this would have to be changed once language_code is supported
				// Note that T3 8.7.4 fails to extract the "ORDER BY" before putting it in a "WHERE", if there are newlines and possibly tabs, due to the limited regular expession employed in \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->processForeignTableClause():1145
				'foreign_table_where' => 'AND tx_decosdata_domain_model_field.pid = ###CURRENT_PID### AND (tx_decosdata_domain_model_field.uid NOT IN ((SELECT f.uid FROM tx_decosdata_domain_model_field f,tx_decosdata_domain_model_profilefield pf WHERE f.uid = pf.field AND pf.profile=###REC_FIELD_profile###)) OR tx_decosdata_domain_model_field.uid = ###REC_FIELD_field###) ORDER BY tx_decosdata_domain_model_field.field_name ASC',
				'minitems' => 1,
				'maxitems' => 1,
			],
		],
		'profile' => [
			'config' => [
				'type' => 'passthrough',
			],
		],

	],
];
