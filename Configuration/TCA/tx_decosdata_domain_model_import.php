<?php

defined('TYPO3') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_import';
$ll = 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;

return [
    'ctrl' => [
        'title' => $ll,
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'dividers2tabs' => 1,
        'default_sortby' => 'ORDER BY title ASC',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        // @TODO __replace icon
        'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif',
    ],
    'types' => [
        '0' => [
            'showitem' => 'hidden, title, file, hash, forget_on_update,
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

        'title' => [
            'exclude' => false,
            'label' => $ll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'uniqueInPid,trim',
                'required' => true,
            ],
        ],
        'file' => [
            'exclude' => false,
            'label' => $ll . '.file',
            'config' => [
                'type' => 'file',
                'allowed' => 'xml',
                'maxitems' => 1,
                'foreign_match_fields' => [
                    'fieldname' => 'file',
                    'tablenames' => $table,
                ],
            ],
        ],
        'hash' => [
            'exclude' => true,
            'label' => $ll . '.hash',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
            ],
        ],
        'forget_on_update' => [
            'exclude' => true,
            'label' => $ll . '.forget_on_update',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],

    ],
];
