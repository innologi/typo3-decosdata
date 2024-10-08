<?php

defined('TYPO3') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_field';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'field_name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [],
        'dividers2tabs' => 1,
        'default_sortby' => 'ORDER BY field_name ASC',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        // @TODO __replace icon
        'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif',
    ],
    'types' => [
        '0' => [
            'showitem' => 'field_name',
        ],
    ],
    'palettes' => [],
    'columns' => [

        'field_name' => [
            'exclude' => false,
            'label' => $ll . '.field_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'uniqueInPid,nospace,trim,alphanum_x,upper',
                'placeholder' => $ll . '.field_name.placeholder',
                'required' => true,
            ],
        ],

    ],
];
