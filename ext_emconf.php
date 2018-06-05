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
	'version' => '0.10.4',
	'constraints' => [
		'depends' => [
			'php' => '7.1',
			'typo3' => '8.7.0-8.7.99',
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
	'autoload' => [
		'psr-4' => [
			'Innologi\\Decosdata\\' => 'Classes'
		]
	]
];