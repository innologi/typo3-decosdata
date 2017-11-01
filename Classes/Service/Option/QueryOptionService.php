<?php
namespace Innologi\Decosdata\Service\Option;
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
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface;
/**
 * Query Option Service
 *
 * Handles the resolving and calling of option class/methods for use in QueryBuilder.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryOptionService extends OptionServiceAbstract {

	/**
	 * @var integer
	 */
	protected $optionIndex;

	/**
	 * Returns Option index
	 *
	 * @return integer
	 */
	public function getOptionIndex() {
		return $this->optionIndex;
	}

	/**
	 * Processes an array of field-options by calling the contained alterQueryField()
	 * methods and passing the necessary arguments to it.
	 *
	 * @param array $options
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface $configuration
	 * @param integer $fieldIndex
	 * @return void
	 */
	public function processFieldOptions(array $options, QueryInterface $configuration, $fieldIndex) {
		$this->index = $fieldIndex;
		foreach ($options as $index => $option) {
			$this->optionIndex = $index;
			$this->executeOption('alterQueryField', $option, $configuration);
		}
	}

	/**
	 * Processes an array of column-options by calling the contained alterQueryColumn()
	 * methods and passing the necessary arguments to it.
	 *
	 * @param array $options
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface $configuration
	 * @param integer $columnIndex
	 * @return void
	 */
	public function processColumnOptions(array $options, QueryInterface $configuration, $columnIndex) {
		$this->index = $columnIndex;
		foreach ($options as $index => $option) {
			$this->optionIndex = $index;
			$this->executeOption('alterQueryColumn', $option, $configuration);
		}
	}

	/**
	 * Processes an array of row-options by calling the contained alterQueryRow()
	 * methods and passing the necessary arguments to it.
	 *
	 * @param array $options
	 * @param \Innologi\Decosdata\Service\QueryBuilder\Query\QueryInterface $configuration
	 * @return void
	 */
	public function processRowOptions(array $options, QueryInterface $configuration) {
		$this->index = 0;
		foreach ($options as $index => $option) {
			$this->optionIndex = $index;
			$this->executeOption('alterQueryRow', $option, $configuration);
		}
	}

}
