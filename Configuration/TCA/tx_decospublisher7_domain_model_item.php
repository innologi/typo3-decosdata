<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extKey = 'decospublisher7';
$table = 'tx_' . $extKey . '_domain_model_item';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;
$llWiz = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_be.xlf:tca_wizard';
// this won't always be up to date because of caching, but that's ok
$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
// for use of similar item relations
$itemRelationWizards = array(
	'_POSITION' => 'bottom',
	'_PADDING' => 3,
	'_DISTANCE' => 5,
	'list' => array(
		'type' => 'script',
		'title' => $llWiz . '.list',
		'icon' => 'list.gif',
		'params' => array(
			'table' => $table,
			'pid' => '###CURRENT_PID###'
		),
		'module' => array(
			'name' => 'wizard_list'
		)
	),
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
);

return array(
	'ctrl' => array(
		'title'	=> $ll,
		'label' => 'uid',
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
		'hideAtCopy' => TRUE,
		'default_sortby' => 'ORDER BY uid DESC',
		// @LOW __I'd like to be able to find item_field.field_value, but this does not seem possible right now
		// instead, we're expanding item_field.item with an edit wizard, see itemfield TCA
		'searchFields' => 'uid, item_field',
		'iconfile' => ExtensionManagementUtility::extRelPath($extKey) . 'Resources/Public/Icons/' . $table . '.gif'
	),

	'interface' => array(
		'showRecordFieldList' => 'hidden, item_key, item_type, import, parent_item, child_item, item_field, item_blob',
	),
	'types' => array(
		'0' => array(
			'showitem' => 'hidden, item_key, item_type, import, parent_item, child_item,
				--div--;' . $ll . '.item_field,item_field,
				--div--;' . $ll . '.item_blob,item_blob,
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
		'item_type' => array(
			'exclude' => FALSE,
			'label' => $ll . '.item_type',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_decospublisher7_domain_model_itemtype',
				'foreign_table_where' => '
					AND tx_decospublisher7_domain_model_itemtype.pid = ###CURRENT_PID###
					ORDER BY tx_decospublisher7_domain_model_itemtype.item_type ASC',
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'import' => array(
			'exclude' => FALSE,
			'label' => $ll . '.import',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_decospublisher7_domain_model_import',
				'foreign_table_where' => '
					AND tx_decospublisher7_domain_model_import.pid = ###CURRENT_PID###
					ORDER BY tx_decospublisher7_domain_model_import.title ASC',
				'MM' => 'tx_decospublisher7_item_import_mm',
				'size' => 6,
				'autoSizeMax' => 15,
				'maxitems' => 9999,
				'multiple' => 0,
				'wizards' => array(
					'_POSITION' => 'bottom',
					'_PADDING' => 3,
					'edit' => array(
						'type' => 'popup',
						'title' => $llWiz . '.edit',
						'icon' => 'edit2.gif',
						'module' => array(
							'name' => 'wizard_edit'
						),
						'popup_onlyOpenIfSelected' => TRUE,
						// @LOW ___resizable=1 ?
						'JSopenParams' => 'height=600,width=800,status=0,menubar=0,scrollbars=1',
					),
				)
			),
		),
		// note that these bidirectional relations don't seem to update references on both sides (6.2.11)
		'parent_item' => array(
			'exclude' => FALSE,
			'label' => $ll . '.parent_item',
			'config' => array(
				'type' => 'select',
				'foreign_table' => $table,
				/*
				 * Couple of restrictions set here:
				 * - Don't show this item itself
				 * - Only show items on same page
				 * - Only show items from the same XML (which we have to do with a subquery instead of a join, ugh)
				 * - Limit the range of items in both directions to some extent
				 */
				'foreign_table_where' => '
					AND ' . $table . '.uid <> ###THIS_UID###
					AND ' . $table . '.pid = ###CURRENT_PID###
					AND ' . $table . '.uid IN (
						SELECT impr1.uid_local
						FROM tx_decospublisher7_item_import_mm impr1
							LEFT JOIN tx_decospublisher7_item_import_mm impr2
								ON (impr1.uid_foreign = impr2.uid_foreign)
						WHERE impr2.uid_local = ###THIS_UID###
					)
					AND ' . $table . '.uid < (###THIS_UID### + 5000)
					AND ' . $table . '.uid > (###THIS_UID### - 10000)
					ORDER BY ' . $table . '.uid ASC',
				// parent = foreign
				'MM' => 'tx_decospublisher7_item_item_mm',
				'size' => 10,
				'autoSizeMax' => 30,
				'maxitems' => 9999,
				'multiple' => 0,
				'enableMultiSelectFilterTextfield' => TRUE,
				'wizards' => $itemRelationWizards
			),
		),
		'child_item' => array(
			'exclude' => FALSE,
			'label' => $ll . '.child_item',
			'config' => array(
				'type' => 'select',
				'foreign_table' => $table,
				// slight variation from parent_item
				'foreign_table_where' => '
					AND ' . $table . '.uid <> ###THIS_UID###
					AND ' . $table . '.pid = ###CURRENT_PID###
					AND ' . $table . '.uid IN (
						SELECT impr1.uid_local
						FROM tx_decospublisher7_item_import_mm impr1
							LEFT JOIN tx_decospublisher7_item_import_mm impr2
								ON (impr1.uid_foreign = impr2.uid_foreign)
						WHERE impr2.uid_local = ###THIS_UID###
					)
					AND ' . $table . '.uid < (###THIS_UID### + 10000)
					AND ' . $table . '.uid > (###THIS_UID### - 5000)
					ORDER BY ' . $table . '.uid DESC',
				// child = local
				'MM' => 'tx_decospublisher7_item_item_mm',
				'MM_opposite_field' => 'parent_item',
				'size' => 10,
				'autoSizeMax' => 30,
				'maxitems' => 9999,
				'multiple' => 0,
				'enableMultiSelectFilterTextfield' => TRUE,
				'wizards' => $itemRelationWizards
			),
		),
		// @LOW __what if we add a wizard or customControl that lets us pick a profile for field-names?
		'item_field' => array(
			'exclude' => FALSE,
			'label' => $ll . '.item_field',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_decospublisher7_domain_model_itemfield',
				'foreign_field' => 'item',
				// this can easily be circumvented, see itemfield.field TCA for solution
				'foreign_unique' => 'field',
				// @LOW __since these are id's now, it seems I can't sort on field.field_name anymore
				'foreign_default_sortby' => 'tx_decospublisher7_domain_model_itemfield.field ASC',
				// override ctrl/label setting
				'foreign_label' => 'field',
				'foreign_selector' => 'field',
				'maxitems' => 9999,
				'appearance' => array(
					'collapseAll' => TRUE,
					'levelLinksPosition' => 'top',
				),
				'behaviour' => array(
					'enableCascadingDelete' => TRUE,
				)
			),
		),
		'item_blob' => array(
			'exclude' => FALSE,
			'label' => $ll . '.item_blob',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_decospublisher7_domain_model_itemblob',
				'foreign_field' => 'item',
				'foreign_default_sortby' => 'tx_decospublisher7_domain_model_itemblob.sequence ASC',
				// override ctrl/label setting
				'foreign_label' => 'sequence',
				'maxitems' => 9999,
				'appearance' => array(
					'collapseAll' => TRUE,
					'levelLinksPosition' => 'top',
				),
				'behaviour' => array(
					'enableCascadingDelete' => TRUE,
				)
			),
		),

	),
);
