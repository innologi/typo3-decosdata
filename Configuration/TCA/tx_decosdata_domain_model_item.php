<?php
defined('TYPO3_MODE') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_item';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
	'ctrl' => [
		'title'	=> $ll,
		'label' => 'uid',
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
		'hideAtCopy' => TRUE,
		'default_sortby' => 'ORDER BY uid DESC',
		// @LOW __I'd like to be able to find item_field.field_value, but this does not seem possible right now
		// instead, we're expanding item_field.item with an edit wizard, see itemfield TCA
		'searchFields' => 'uid, item_field',
		'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif'
	],

	'types' => [
		'0' => [
			'showitem' => 'hidden, item_key, item_type, import, parent_item, child_item,
				--div--;' . $ll . '.item_field,item_field,
				--div--;' . $ll . '.item_blob,item_blob,
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
		'item_type' => [
			'exclude' => FALSE,
			'label' => $ll . '.item_type',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tx_decosdata_domain_model_itemtype',
				// Note that T3 8.7.4 fails to extract the "ORDER BY" before putting it in a "WHERE", if there are newlines and possibly tabs, due to the limited regular expession employed in \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->processForeignTableClause():1145
				'foreign_table_where' => 'AND tx_decosdata_domain_model_itemtype.pid = ###CURRENT_PID### ORDER BY tx_decosdata_domain_model_itemtype.item_type ASC',
				'minitems' => 1,
				'maxitems' => 1,
			],
		],
		'import' => [
			'exclude' => FALSE,
			'label' => $ll . '.import',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectMultipleSideBySide',
				'foreign_table' => 'tx_decosdata_domain_model_import',
				// Note that T3 8.7.4 fails to extract the "ORDER BY" before putting it in a "WHERE", if there are newlines and possibly tabs, due to the limited regular expession employed in \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->processForeignTableClause():1145
				'foreign_table_where' => 'AND tx_decosdata_domain_model_import.pid = ###CURRENT_PID### ORDER BY tx_decosdata_domain_model_import.title ASC',
				'MM' => 'tx_decosdata_item_import_mm',
				'size' => 6,
				'autoSizeMax' => 15,
				'maxitems' => 9999,
				'multiple' => 0,
				'fieldControl' => [
					'editPopup' => [
						'disabled' => FALSE,
					]
				]
			],
		],
		// note that these bidirectional relations don't seem to update references on both sides (6.2.11)
		'parent_item' => [
			'exclude' => FALSE,
			'label' => $ll . '.parent_item',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectMultipleSideBySide',
				'foreign_table' => $table,
				/*
				 * Couple of restrictions set here:
				 * - Don't show this item itself
				 * - Only show items on same page
				 * - Only show items from the same XML (which we have to do with a subquery instead of a join, ugh)
				 * - Limit the range of items in both directions to some extent
				 */
				// Note that T3 8.7.4 fails to extract the "ORDER BY" before putting it in a "WHERE", if there are newlines and possibly tabs, due to the limited regular expession employed in \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->processForeignTableClause():1145
				'foreign_table_where' => 'AND ' . $table . '.uid <> ###THIS_UID### AND ' . $table . '.pid = ###CURRENT_PID### AND ' . $table . '.uid IN (SELECT impr1.uid_local FROM tx_decosdata_item_import_mm impr1 LEFT JOIN tx_decosdata_item_import_mm impr2 ON impr1.uid_foreign = impr2.uid_foreign WHERE impr2.uid_local = ###THIS_UID###) AND ' . $table . '.uid < (###THIS_UID### + 5000) AND ' . $table . '.uid > (###THIS_UID### - 10000) ORDER BY ' . $table . '.uid ASC',
				// parent = foreign
				'MM' => 'tx_decosdata_item_item_mm',
				'size' => 10,
				'autoSizeMax' => 30,
				'maxitems' => 9999,
				'multiple' => 0,
				'fieldControl' => [
					'editPopup' => [
						'disabled' => FALSE,
					],
					'listModule' => [
						'disabled' => FALSE,
					]
				]
			],
		],
		'child_item' => [
			'exclude' => FALSE,
			'label' => $ll . '.child_item',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectMultipleSideBySide',
				'foreign_table' => $table,
				// slight variation from parent_item
				// Note that T3 8.7.4 fails to extract the "ORDER BY" before putting it in a "WHERE", if there are newlines and possibly tabs, due to the limited regular expession employed in \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->processForeignTableClause():1145
				'foreign_table_where' => 'AND ' . $table . '.uid <> ###THIS_UID### AND ' . $table . '.pid = ###CURRENT_PID### AND ' . $table . '.uid IN (SELECT impr1.uid_local FROM tx_decosdata_item_import_mm impr1 LEFT JOIN tx_decosdata_item_import_mm impr2 ON impr1.uid_foreign = impr2.uid_foreign WHERE impr2.uid_local = ###THIS_UID###) AND ' . $table . '.uid < (###THIS_UID### + 10000) AND ' . $table . '.uid > (###THIS_UID### - 5000) ORDER BY ' . $table . '.uid DESC',
				// child = local
				'MM' => 'tx_decosdata_item_item_mm',
				'MM_opposite_field' => 'parent_item',
				'size' => 10,
				'autoSizeMax' => 30,
				'maxitems' => 9999,
				'multiple' => 0,
				'fieldControl' => [
					'editPopup' => [
						'disabled' => FALSE,
					],
					'listModule' => [
						'disabled' => FALSE,
					]
				]
			],
		],
		// @LOW __what if we add a wizard or customControl that lets us pick a profile for field-names?
		'item_field' => [
			'exclude' => FALSE,
			'label' => $ll . '.item_field',
			'config' => [
				'type' => 'inline',
				'foreign_table' => 'tx_decosdata_domain_model_itemfield',
				'foreign_field' => 'item',
				// this can easily be circumvented, see itemfield.field TCA for solution
				'foreign_unique' => 'field',
				// @LOW __since these are id's now, it seems I can't sort on field.field_name anymore
				'foreign_default_sortby' => 'tx_decosdata_domain_model_itemfield.field ASC',
				// override ctrl/label setting
				'foreign_label' => 'field',
				'foreign_selector' => 'field',
				'maxitems' => 9999,
				'appearance' => [
					'collapseAll' => TRUE,
					'levelLinksPosition' => 'top',
				],
				'behaviour' => [
					'enableCascadingDelete' => TRUE,
				]
			],
		],
		'item_blob' => [
			'exclude' => FALSE,
			'label' => $ll . '.item_blob',
			'config' => [
				'type' => 'inline',
				'foreign_table' => 'tx_decosdata_domain_model_itemblob',
				'foreign_field' => 'item',
				'foreign_default_sortby' => 'tx_decosdata_domain_model_itemblob.sequence ASC',
				// override ctrl/label setting
				'foreign_label' => 'sequence',
				'maxitems' => 9999,
				'appearance' => [
					'collapseAll' => TRUE,
					'levelLinksPosition' => 'top',
				],
				'behaviour' => [
					'enableCascadingDelete' => TRUE,
				]
			],
		],

	],
];
