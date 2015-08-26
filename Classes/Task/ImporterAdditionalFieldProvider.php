<?php
namespace Innologi\Decosdata\Task;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
/**
 * Importer Additional Field Provider
 *
 * Provides an import selection to the task.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImporterAdditionalFieldProvider implements AdditionalFieldProviderInterface {

	/**
	 * @var string
	 */
	protected $ll = 'LLL:EXT:decosdata/Resources/Private/Language/locallang_be.xlf:';

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param array $taskInfo Values of the fields from the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being edited. Null when adding a task!
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		// set field value
		if (empty($taskInfo['selectedImports'])) {
			switch ($schedulerModule->CMD) {
				case 'edit':
					// existing task, meaning there is a value
					$taskInfo['selectedImports'] = $task->selectedImports;
					break;
				default:
					$taskInfo['selectedImports'] = array();
			}
		}

		// provide HTML parameters
		$fieldName = 'tx_scheduler[selectedImports][]';
		$fieldId = 'task_selectedImports';
		$fieldOptions = $this->getImportOptions($taskInfo['selectedImports']);
		$fieldHtml = '<select name="' . $fieldName . '" id="' . $fieldId . '" class="wide" size="10" multiple="multiple">' . $fieldOptions . '</select>';
		$additionalFields = array(
			$fieldId => array(
				'code' => $fieldHtml,
				'label' => $this->ll . 'task_importer.field.selectImports',
				'cshKey' => 'tx_decosdata_task_importer',
				'cshLabel' => $fieldId
			)
		);
		return $additionalFields;
	}

	/**
	 * Validates the additional fields' values
	 *
	 * @param array &$submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	*/
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$valid = FALSE;
		$availableImports = $this->findAllImports();
		if (!is_array($submittedData['selectedImports'])) {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL($this->ll . 'task_importer.msg.noImportSelected'),
				FlashMessage::ERROR
			);
		} else {
			$invalidImports = array_diff($submittedData['selectedImports'], array_keys($availableImports));
			if (!empty($invalidImports)) {
				$schedulerModule->addMessage(
					$GLOBALS['LANG']->sL($this->ll . 'task_importer.msg.invalidImportSelected'),
					FlashMessage::ERROR
				);
			} else {
				$valid = TRUE;
			}
		}
		return $valid;
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the scheduler backend module
	 * @return void
	*/
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		$task->selectedImports = $submittedData['selectedImports'];
	}

	/**
	 * Finds all Imports regardless of pid, returns as an array
	 * with values uid, pid, title, and uid as key.
	 *
	 * This method does not use the repository, because the task is TYPO3-specific
	 * anyway and this way it is way faster.
	 *
	 * @return array|NULL
	 */
	protected function findAllImports() {
		/* @var $databaseConnection \TYPO3\CMS\Core\Database\DatabaseConnection */
		$databaseConnection = $GLOBALS['TYPO3_DB'];
		return $databaseConnection->exec_SELECTgetRows(
			'uid,pid,title',
			'tx_decosdata_domain_model_import',
			'deleted=0',
			'',
			'title ASC',
			'',
			'uid'
		);
	}

	/**
	 * Build select options of available imports and set currently selected imports,
	 * for an HTML select element.
	 *
	 * @param array $selectedImports Selected imports
	 * @return string HTML of selectbox options
	 */
	protected function getImportOptions(array $selectedImports = array()) {
		$options = array();
		$imports = $this->findAllImports();
		foreach ($imports as $uid => $import) {
			$selected = in_array($uid, $selectedImports) ? ' selected="selected"' : '';
			$options[] = '<option value="' . $uid . '"' . $selected . '>' . $import['title'] . ' [pid:' . $import['pid'] . ']</option>';
		}
		return join('', $options);
	}

}
