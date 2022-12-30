<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "decosdata"
 *
 * Auto generated by Extension Builder 2015-03-11
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'Decos Data',
	'description' => 'Import Decos XML exports locally and flexibly publish their contents. Successor to the \'decospublisher\' extension.',
	'category' => 'plugin',
	'author' => 'Frenck Lutke',
	'author_email' => 'typo3@innologi.nl',
	'author_company' => 'www.innologi.nl',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 1,
	'version' => '1.1.0',
	'constraints' => [
		'depends' => [
			'php' => '7.2',
			'typo3' => '10.4.0-11.99.99',
		],
		'conflicts' => [
		],
		'suggests' => [
			'scheduler' => '10.4.0',
			'typo3db_legacy' => '1.1.4'
		],
	],
	'autoload' => [
		'psr-4' => [
			'Innologi\\Decosdata\\' => 'Classes'
		]
	]
];