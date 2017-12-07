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
use Symfony\Component\Console\Style\SymfonyStyle;
/**
 * Ext Update Abstract
 *
 * @package InnologiLibs
 * @subpackage ExtUpdate
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class ExtUpdateAbstract implements ExtUpdateInterface {
	// @LOW ___what about a reload button?
	// @TODO ___give ext_update a namespace, seems we can get rid of typo3temp version that way!
	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 */
	private $flashMessageQueue;

	/**
	 * @var \Symfony\Component\Console\Style\SymfonyStyle
	 */
	protected $io;

	/**
	 * @var boolean
	 */
	protected $cliMode = FALSE;

	/**
	 * @var array
	 */
	protected $cliAllowedSeverities = [
		FlashMessage::OK => 'success',
		FlashMessage::WARNING => 'warning',
		FlashMessage::ERROR => 'error'
	];

	/**
	 * @var array
	 */
	protected $cliErrorSeverities = [
		FlashMessage::WARNING => TRUE,
		FlashMessage::ERROR => TRUE
	];

	/**
	 * @var integer
	 */
	protected $errorCount = 0;

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
	 * If the source extension is not loaded, setting this override will
	 * ignore the requirement if the extension's tables are found.
	 *
	 * @var string
	 */
	protected $overrideSourceRequirement = FALSE;

	/**
	 * Constructor
	 *
	 * If called from CLI, the parameter will be used to print messages.
	 *
	 * @param \Symfony\Component\Console\Style\SymfonyStyle $io
	 * @param array $arguments
	 * @return void
	 * @throws Exception\NoExtkeySet
	 */
	public function __construct(SymfonyStyle $io = NULL, array $arguments = []) {
		if ( !isset($this->extensionKey[0]) ) {
			throw new Exception\NoExtkeySet(1448616492);
		}
		// generally, the source-extension is the same as the current one
		if ( !isset($this->sourceExtensionKey[0]) ) {
			$this->sourceExtensionKey = $this->extensionKey;
		}

		/* @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
		$this->databaseService = $objectManager->get(
			__NAMESPACE__ . '\\Service\\DatabaseService'
		);
		$this->fileService = $objectManager->get(
			__NAMESPACE__ . '\\Service\\FileService'
		);

		// determine mode
		$this->cliMode = TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI;
		if ($this->cliMode) {
			// in CLI mode, messages are to be printed
			if ($io === NULL) {
				throw new Exception\ImproperCliInit(1461682094);
			}
			$this->io = $io;
			$this->overrideSourceRequirement = $arguments['overrideSourceRequirement'] ?? FALSE;
		} else {
			// in normal (non-CLI) mode, messages are queued
			$this->flashMessageQueue = $objectManager->get(
				\TYPO3\CMS\Core\Messaging\FlashMessageQueue::class,
				'extbase.flashmessages.tx_' . $this->extensionKey . '_extupdate'
			);
		}
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
			// CLI mode will loop until finished
			do {
				$finished = $this->processUpdates();
			} while ($this->cliMode && $this->errorCount < 1 && !$finished);

			// if not finished, we'll add the instruction to run the updater again
			if ($finished) {
				$this->addMessage(
					'The updater has finished all of its tasks, you don\'t need to run it again until the next extension-update.',
					'Update complete',
					FlashMessage::OK
				);
			} else {
				$this->addMessage(
					'Please run the updater again to continue updating and/or follow any remaining instructions, until this message disappears.',
					'Run updater again',
					FlashMessage::WARNING
				);
			}
		} catch (Exception\Exception $e) {
			$this->addMessage(
				$e->getFormattedErrorMessage(),
				'Update failed',
				FlashMessage::ERROR
			);
		} catch (\Exception $e) {
			$this->addMessage(
				$e->getMessage(),
				'Update failed',
				FlashMessage::ERROR
			);
		}
		return $this->cliMode ? '' : $this->flashMessageQueue->renderFlashMessages();
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
	 * @throws Exception\ExtensionNotLoaded
	 * @throws Exception\IncorrectExtensionVersion
	 */
	protected function checkPrerequisites() {
		if ($this->extensionKey !== $this->sourceExtensionKey) {
			// we don't use em_conf for this, because the requirement is only for
			// the updater, not the entire extension

			// is source extension loaded?
			if (!ExtensionManagementUtility::isLoaded($this->sourceExtensionKey)) {
				if ($this->overrideSourceRequirement) {
					if ($this->databaseService->doExtensionTablesExist($this->sourceExtensionKey)) {
						// found at least one table
						return;
					}
				}
				throw new Exception\ExtensionNotLoaded(1448616650, array($this->sourceExtensionKey));
			}
			// does source extension meet version requirement?
			if (version_compare(
				ExtensionManagementUtility::getExtensionVersion($this->sourceExtensionKey),
				$this->sourceExtensionVersion,
				'<'
			)) {
				throw new Exception\IncorrectExtensionVersion(1448616744, array(
					$this->sourceExtensionKey, $this->sourceExtensionVersion
				));
			}
		}
	}

	/**
	 * Adds a message to output
	 *
	 * @param string $messageBody The message
	 * @param string $messageTitle Optional message title (only for non-CLI)
	 * @param integer $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
	 * @return void
	 */
	protected function addMessage($messageBody, $messageTitle = '', $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		if ($this->cliMode) {
			if (isset($this->cliAllowedSeverities[$severity])) {
				$this->io->{$this->cliAllowedSeverities[$severity]}($messageBody);
			}
			if (isset($this->cliErrorSeverities[$severity])) {
				$this->errorCount++;
			}
		} else {
			/* @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
			$flashMessage = GeneralUtility::makeInstance(
				\TYPO3\CMS\Core\Messaging\FlashMessage::class, $messageBody, $messageTitle, $severity
			);
			$this->flashMessageQueue->enqueue($flashMessage);
		}
	}

}
