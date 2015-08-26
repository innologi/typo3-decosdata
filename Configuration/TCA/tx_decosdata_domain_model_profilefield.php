<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_profilefield';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return array(
	'ctrl' => array(
		'title'	=> $ll,
		'label' => 'field',
		'label_alt' => 'profile',
		'label_alt_force' => TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY profile DESC, field ASC',
		// only used in profile IRRE
		'hideTable' => TRUE,
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden, description, field, profile',
	),
	'types' => array(
		'0' => array(
			'showitem' => 'hidden, description, field, profile',
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

		'description' => array(
			'exclude' => FALSE,
			'label' => $ll . '.description',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,required'
			),
		),
		'field' => array(
			'exclude' => FALSE,
			'label' => $ll . '.field',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_decosdata_domain_model_field',
				// the 2nd AND forces unique field per profile
				// note that this would have to be changed once language_code is supported
				'foreign_table_where' => '
					AND tx_decosdata_domain_model_field.pid = ###CURRENT_PID###
					AND (
						tx_decosdata_domain_model_field.uid NOT IN ((
							SELECT f.uid
							FROM tx_decosdata_domain_model_field f,tx_decosdata_domain_model_profilefield pf
							WHERE f.uid = pf.field
								AND pf.profile=###REC_FIELD_profile###
						))
						OR tx_decosdata_domain_model_field.uid = ###REC_FIELD_field###
					)
					ORDER BY tx_decosdata_domain_model_field.field_name ASC',
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'profile' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),

	),
);
