<?php
defined('TYPO3_MODE') or die();

$tablePrefix = 'tx_' . $_EXTKEY . '_domain_model_';
$cshPathPrefix = 'EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_csh_';

// allow records of these tables to be stored on standard page types
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'profile');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'profilefield');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'import');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'field');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'item');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'itemblob');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'itemfield');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages($tablePrefix . 'itemtype');

// add CSH files to TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'profile',
	$cshPathPrefix . 'tca_profile.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'profilefield',
	$cshPathPrefix . 'tca_profilefield.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'import',
	$cshPathPrefix . 'tca_import.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'field',
	$cshPathPrefix . 'tca_field.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'item',
	$cshPathPrefix . 'tca_item.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'itemblob',
	$cshPathPrefix . 'tca_blob.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'itemfield',
	$cshPathPrefix . 'tca_itemfield.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	$tablePrefix . 'itemtype',
	$cshPathPrefix . 'tca_itemtype.xlf'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
	'tx_' . $_EXTKEY . '_task_importer',
	$cshPathPrefix . 'task_importer.xlf'
);

if (\version_compare(TYPO3_version, '9.5', '>=')) {
	// Add module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		\Innologi\Decosdata\Modfunc\Module::class,
		null,
		'Decosdata'
	);
}