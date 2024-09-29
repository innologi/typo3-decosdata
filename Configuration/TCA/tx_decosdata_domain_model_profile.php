<?php

defined('TYPO3') or die();

$extKey = 'decosdata';
$table = 'tx_' . $extKey . '_domain_model_profile';
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
        ],
        'dividers2tabs' => 1,
        'default_sortby' => 'ORDER BY title ASC',
        'iconfile' => 'EXT:' . $extKey . '/Resources/Public/Icons/' . $table . '.gif',
    ],
    'types' => [
        '0' => [
            'showitem' => 'hidden, title, file, profile_key,
				--div--;' . $ll . '.profile_field,profile_field',
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
            ],
        ],
        'profile_key' => [
            'exclude' => false,
            'label' => $ll . '.profile_key',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 32,
                'eval' => 'uniqueInPid,trim,nospace,alphanum,upper',
                'required' => true,
            ],
        ],
        'profile_field' => [
            'exclude' => false,
            'label' => $ll . '.profile_field',
            'config' => [
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
                /*'foreign_match_fields' => [
                    'language_code' => 'nl',
                ],
                'filter => ..*/
                'maxitems' => 9999,
                'appearance' => [
                    'collapseAll' => true,
                    'levelLinksPosition' => 'top',
                ],
                'behaviour' => [
                    'enableCascadingDelete' => true,
                ],
            ],
        ],
    ],
];
