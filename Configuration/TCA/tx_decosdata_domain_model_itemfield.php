<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_itemfield';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;
$llWiz = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_be.xlf:tca_wizard';

return array(
	'ctrl' => array(
		'title'	=> $ll,
		'label' => 'field',
		'label_alt' => 'item',
		'label_alt_force' => TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dividers2tabs' => 1,
		'default_sortby' => 'ORDER BY item DESC, field ASC',
		// only used in item IRRE
		'hideTable' => TRUE,
		'hideAtCopy' => TRUE,
		'searchFields' => 'field_value',
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden, field_value, field, item',
	),
	'types' => array(
		'0' => array(
			'showitem' => 'hidden, field_value, field, item'
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
		// @LOW __should this be varchar(255) or text? research!
		'field_value' => array(
			'exclude' => FALSE,
			'label' => $ll . '.field_value',
			'config' => array(
				'type' => 'text',
				'cols' => 50,
				'rows' => 15,
				'eval' => 'trim'
			),
			'search' => array(
				// search speed up
				'andWhere' => 'field_value != \'\'',
			)
		),
		'field' => array(
			'exclude' => FALSE,
			'label' => $ll . '.field',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_decosdata_domain_model_field',
				// the 2nd AND forces unique field per item
				'foreign_table_where' => '
					AND tx_decosdata_domain_model_field.pid = ###CURRENT_PID###
					AND (
						tx_decosdata_domain_model_field.uid NOT IN ((
							SELECT f.uid
							FROM tx_decosdata_domain_model_field f,tx_decosdata_domain_model_itemfield itf
							WHERE f.uid = itf.field
								AND itf.item=###REC_FIELD_item###
						))
						OR tx_decosdata_domain_model_field.uid = ###REC_FIELD_field###
					)
					ORDER BY tx_decosdata_domain_model_field.field_name ASC',
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		// this is really only added for livesearch benefit, this helps a user looking
		// for an item by item field value, via edit-wizard (field is hidden in IRRE)
		'item' => array(
			'exclude' => FALSE,
			'label' => $ll . '.item',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_decosdata_domain_model_item',
				// the 2nd AND limits items to those from same import
				'foreign_table_where' => '
					AND tx_decosdata_domain_model_item.pid = ###CURRENT_PID###
					AND (
						tx_decosdata_domain_model_item.tstamp = ###REC_FIELD_tstamp###
						OR tx_decosdata_domain_model_item.uid = ###REC_FIELD_item###
					)
					ORDER BY tx_decosdata_domain_model_item.uid ASC',
				'minitems' => 1,
				'maxitems' => 1,
				'wizards' => array(
					#'_POSITION' => 'bottom',
					'_PADDING' => 3,
					'edit' => array(
						'type' => 'popup',
						'title' => $llWiz . '.edit',
						'icon' => 'edit2.gif',
						'module' => array(
							'name' => 'wizard_edit'
						),
						'popup_onlyOpenIfSelected' => TRUE,
						'JSopenParams' => 'height=600,width=800,status=0,menubar=0,scrollbars=1',
					),
				)
			),
		),

	),
);