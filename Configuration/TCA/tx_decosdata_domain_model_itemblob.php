<?php

defined('TYPO3') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_itemblob';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
    'ctrl' => [
        'title' => $ll,
        // @LOW ___ for more descriptive TCA labels, check out http://docs.typo3.org/typo3cms/TCAReference/Reference/Ctrl/Index.html#label-userfunc
        'label' => 'sequence',
        'label_alt' => 'item',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'dividers2tabs' => 1,
        'hideTable' => true,
        'hideAtCopy' => true,
        'default_sortby' => 'ORDER BY uid DESC',
        // the table is non-searchable
        //'searchFields' => '',
        'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif',
    ],

    'types' => [
        '0' => [
            'showitem' => 'hidden, item_key, sequence, file,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime',
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
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],

        'item_key' => [
            'exclude' => false,
            'label' => $ll . '.item_key',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 32,
                'eval' => 'required,uniqueInPid,trim,nospace,alphanum,upper',
            ],
        ],
        'sequence' => [
            'exclude' => false,
            'label' => $ll . '.sequence',
            'config' => [
                'type' => 'number',
                'size' => 40,
                'max' => 32,
                'eval' => 'required',
            ],
        ],
        'file' => [
            'exclude' => false,
            'label' => $ll . '.file',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'foreign_match_fields' => [
                    'fieldname' => 'file',
                    'tablenames' => $table,
                ],
            ],
        ],
        'item' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

    ],
];
