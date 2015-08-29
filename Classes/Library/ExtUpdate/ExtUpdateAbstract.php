<?php
namespace Innologi\Decosdata\Library\ExtUpdate;
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
/**
 * Ext Update Abstract
 *
 * @package InnologiLibs
 * @subpackage ExtUpdate
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class ExtUpdateAbstract implements ExtUpdateInterface{
	// @LOW ___what about a reload button?
	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 */
	protected $flashMessageQueue;

	/**
	 * @var \Innologi\Decosdata\Library\ExtUpdate\Service\DatabaseService
	 */
	protected $databaseService;

	/**
	 * @var \Innologi\Decosdata\Library\ExtUpdate\Service\FileService
	 */
	protected $fileService;

	/**
	 * Extension key
	 *
	 * @var string
	 */
	protected $extensionKey;

	/**
	 * If the extension updater is to take data from a different source,
	 * its extension key may be set here.
	 *
	 * @var string
	 */
	protected $sourceExtensionKey;

	/**
	 * If the extension updater is to take data from a different source,
	 * its minimum version may be set here.
	 *
	 * @var string
	 */
	protected $sourceExtensionVersion = '0.0.0';

	/**
	 * Static language labels => messages
	 *
	 * DO NOT OVERRULE!
	 *
	 * @var array
	 */
	protected $staticLang = array(
		'extNotLoaded' => 'Source extension \'<code>%1$s</code>\' is not loaded, cannot run updater.',
		'extIncorrectVersion' => 'Source extension \'<code>%1$s</code>\' needs to be updated to version <code>%2$s</code>.',
		'noExtKeySet' => 'The extension updater class has no extension key set. You need to override \'$extensionKey\' in your ext_update class.'
	);

	/**
	 * Constructor
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function __construct() {
		if ( !isset($this->extensionKey[0]) ) {
			throw new \Exception($this->staticLang['noExtKeySet']);
		}
		// generally, the source-extension is the same as the current one
		if ( !isset($this->sourceExtensionKey[0]) ) {
			$this->sourceExtensionKey = $this->extensionKey;
		}
		/* @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

		$this->flashMessageQueue = $objectManager->get(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue',
			'extbase.flashmessages.tx_' . $this->extensionKey . '_extupdate'
		);

		$this->databaseService = $objectManager->get(
			__NAMESPACE__ . '\\Service\\DatabaseService'
		);
		$this->fileService = $objectManager->get(
			__NAMESPACE__ . '\\Service\\FileService'
		);
	}

	/**
	 * This method is called by the extension manager to execute updates.
	 *
	 * Any exception thrown will be captured and converted to a flash message.
	 *
	 * @return string
	 */
	public function main() {
		try {
			$this->checkPrerequisites();
			$this->processUpdates();
		} catch (\Exception $e) {
			$this->addFlashMessage(
				$e->getMessage(),
				'Update failed',
				FlashMessage::ERROR
			);
		}
		return $this->flashMessageQueue->renderFlashMessages();
	}

	/**
	 * This method is called by the extension manager to determine
	 * whether it is allowed to execute the update scripts.
	 *
	 * You can overrule this method to provide any access-logic
	 * you see fit.
	 *
	 * @return boolean
	 */
	public function access() {
		return TRUE;
	}

	/**
	 * Checks updater prerequisites. Throws exceptions if not met.
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function checkPrerequisites() {
		if ($this->extensionKey !== $this->sourceExtensionKey) {
			// we don't use em_conf for this, because the requirement is only for
			// the updater, not the entire extension

			// is source extension is loaded?
			if (!ExtensionManagementUtility::isLoaded($this->sourceExtensionKey)) {
				throw new \Exception(
					sprintf(
						$this->staticLang['extNotLoaded'],
						$this->sourceExtensionKey
					)
				);
			}
			// does source extension meet version requirement?
			if (version_compare(
				ExtensionManagementUtility::getExtensionVersion($this->sourceExtensionKey),
				$this->sourceExtensionVersion,
				'<'
			)) {
				throw new \Exception(
					sprintf(
						$this->staticLang['extIncorrectVersion'],
						$this->sourceExtensionKey,
						$this->sourceExtensionVersion
					)
				);
			}
		}
	}

	/**
	 * Creates a Message object and adds it to the FlashMessageQueue.
	 *
	 * @param string $messageBody The message
	 * @param string $messageTitle Optional message title
	 * @param integer $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
	 * @return void
	 */
	protected function addFlashMessage($messageBody, $messageTitle = '', $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		/* @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
		$flashMessage = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $messageBody, $messageTitle, $severity
		);
		$this->flashMessageQueue->enqueue($flashMessage);
	}

}
