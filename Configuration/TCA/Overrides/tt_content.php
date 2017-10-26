<?php
defined('TYPO3_MODE') or die();

// add publish-plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	'Innologi.Decosdata',
	'Publish',
	'Publish Decos Data'
);