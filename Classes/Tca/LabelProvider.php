<?php
namespace Innologi\Decospublisher7\Tca;

class LabelProvider {

	public function itemFieldLabel(&$parameters, $parentObject) {
		$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('tx_decospublisher7_domain_model_field', $parameters['row']['field']);
		$parameters['title'] = $record['field_name'];
	}

}