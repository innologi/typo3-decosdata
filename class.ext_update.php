<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
 * Ext Update
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ext_update {

	/**
	 * This method is called by the extension manager to execute updates.
	 *
	 * Any exception thrown will be captured and converted to a flash message.
	 *
	 * @return string
	 */
	public function main() {
		/** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $flashMessageQueue */
		$flashMessageQueue = GeneralUtility::makeInstance(
			\TYPO3\CMS\Extbase\Object\ObjectManager::class
		)->get(
			\TYPO3\CMS\Core\Messaging\FlashMessageQueue::class,
			'extbase.flashmessages.tx_decosdata_extupdate'
		);
		$flashMessageQueue->enqueue(
			GeneralUtility::makeInstance(
				FlashMessage::class,
				'Execute this command from TYPO3 root to find out more about migrating from decospublisher.',
				'./vendor/bin/typo3 help decosdata:migrate',
				FlashMessage::INFO
			)
		);
		return $flashMessageQueue->renderFlashMessages();
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

}
