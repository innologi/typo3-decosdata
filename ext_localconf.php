<?php
defined('TYPO3_MODE') or die();

$ll = 'LLL:EXT:decosdata/Resources/Private/Language/locallang_be.xlf:';

// plugin configuration
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'Decosdata',
	'Publish',
	[
		\Innologi\Decosdata\Controller\ItemController::class => 'multi,single,search'
	],
	// non-cacheable actions
	[
	    \Innologi\Decosdata\Controller\ItemController::class => 'search'
	]
);

// add scheduler tasks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Innologi\Decosdata\Task\ImporterTask::class] = [
	'extension'        => 'decosdata',
	'title'            => $ll . 'task_importer.title',
	'description'      => $ll . 'task_importer.description',
	'additionalFields' => \Innologi\Decosdata\Task\ImporterAdditionalFieldProvider::class
];

// add eid scripts
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_decosdata_download'] = \Innologi\Decosdata\Eid\Download::class;

// routing
$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['enhancers']['decosdata_EnhancedExtbase'] = \Innologi\Decosdata\Routing\Enhancer\EnhancedExtbasePluginEnhancer::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['decosdata_FlexiblePersistedAliasMapper'] = \Innologi\Decosdata\Routing\Aspect\FlexiblePersistedAliasMapper::class;

// register implementation classes for DI
/** @var \TYPO3\CMS\Extbase\Object\Container\Container $container */
$container = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
$container->registerImplementation(
    \Innologi\Decosdata\Service\Importer\Parser\ParserInterface::class,
    \Innologi\Decosdata\Service\Importer\Parser\OneFileStreamingParser::class
);
$container->registerImplementation(
    \Innologi\Decosdata\Service\Importer\StorageHandler\StorageHandlerInterface::class,
    \Innologi\Decosdata\Service\Importer\StorageHandler\ClassicStorageHandler::class
);
$container->registerImplementation(
    \Innologi\Decosdata\Service\Database\QueryProviderInterface::class,
    \Innologi\Decosdata\Service\Database\MysqlQueryProvider::class
);