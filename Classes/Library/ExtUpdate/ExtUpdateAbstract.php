<?php
namespace Innologi\Decospublisher7\Library\ExtUpdate;
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
/**
 * Ext Update Abstract
 *
 * @package InnologiLibs
 * @subpackage ExtUpdate
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class ExtUpdateAbstract implements ExtUpdateInterface{

	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 */
	protected $flashMessageQueue;

	/**
	 * @var \Innologi\Decospublisher7\Library\ExtUpdate\Service\DatabaseService
	 */
	protected $databaseService;

	/**
	 * Extension key
	 *
	 * @var string
	 */
	protected $extensionKey;

	/**
	 * If the extension updater is to take data from a different extension,
	 * its extension key may be set here.
	 *
	 * @var string
	 */
	protected $dataExtensionKey;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		if ( !isset($this->$extensionKey[0]) ) {
			throw new \Exception('The extension updater class has no extension key set. You need to override \'$extensionKey\' in your ext_update class.');
		}
		// generally, the data-extension is the same as the current one
		if ( !isset($this->dataExtensionKey[0]) ) {
			$this->dataExtensionKey = $this->extensionKey;
		}

		$this->flashMessageQueue = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessageQueue',
			'extbase.flashmessages.tx_' . $this->extensionKey . '_extupdate'
		);
		$this->databaseService = GeneralUtility::makeInstance(
			__NAMESPACE__ . '\\Service\\DatabaseService'
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
			$this->processUpdates();
		} catch (\Exception $e) {
			$this->addFlashMessage(
				$e->getMessage(),
				'',
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
