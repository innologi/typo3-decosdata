<?php

defined('TYPO3') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_itemfield';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'field',
        'label_alt' => 'item',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'dividers2tabs' => 1,
        'default_sortby' => 'ORDER BY item DESC, field ASC',
        // only used in item IRRE
        'hideTable' => true,
        'hideAtCopy' => true,
        'searchFields' => 'field_value',
        'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif',
    ],
    'types' => [
        '0' => [
            'showitem' => 'hidden, field_value, field, item',
        ],
    ],
    'palettes' => [],
    'columns' => [

        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'field_value' => [
            'exclude' => false,
            'label' => $ll . '.field_value',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 15,
                'eval' => 'trim',
                'default' => '',
            ],
            'search' => [
                // search speed up
                'andWhere' => 'field_value != \'\'',
            ],
        ],
        'field' => [
            'exclude' => false,
            'label' => $ll . '.field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_decosdata_domain_model_field',
                // the 2nd AND forces unique field per item
                // Note that T3 8.7.4 fails to extract the "ORDER BY" before putting it in a "WHERE", if there are newlines and possibly tabs, due to the limited regular expession employed in \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->processForeignTableClause():1145
                'foreign_table_where' => 'AND tx_decosdata_domain_model_field.pid = ###CURRENT_PID### AND (tx_decosdata_domain_model_field.uid NOT IN ((SELECT f.uid FROM tx_decosdata_domain_model_field f,tx_decosdata_domain_model_itemfield itf WHERE f.uid = itf.field AND itf.item=###REC_FIELD_item###)) OR tx_decosdata_domain_model_field.uid = ###REC_FIELD_field###) ORDER BY tx_decosdata_domain_model_field.field_name ASC',
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        // this is really only added for livesearch benefit, this helps a user looking
        // for an item by item field value, via edit-wizard (field is hidden in IRRE)
        'item' => [
            'exclude' => false,
            'label' => $ll . '.item',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_decosdata_domain_model_item',
                // the 2nd AND limits items to those from same import
                // Note that T3 8.7.4 fails to extract the "ORDER BY" before putting it in a "WHERE", if there are newlines and possibly tabs, due to the limited regular expession employed in \TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider->processForeignTableClause():1145
                'foreign_table_where' => 'AND tx_decosdata_domain_model_item.pid = ###CURRENT_PID### AND (tx_decosdata_domain_model_item.tstamp = ###REC_FIELD_tstamp### OR tx_decosdata_domain_model_item.uid = ###REC_FIELD_item###) ORDER BY tx_decosdata_domain_model_item.uid ASC',
                'minitems' => 1,
                'maxitems' => 1,
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],

    ],
];
