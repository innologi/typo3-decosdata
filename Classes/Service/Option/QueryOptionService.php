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
use Innologi\Decosdata\Service\QueryBuilder\QueryBuilder;
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

	// @TODO ___check to see if we really need a $queryBuilder reference in any option class, once we're finished. Because it doesn't seem like it, since we use $queryConfiguration
	/**
	 * Processes an array of field-options by calling the contained alterQueryField()
	 * methods and passing a queryConfiguration reference and queryBuilder object to it.
	 *
	 * @param array $options
	 * @param array &$queryConfiguration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder $queryBuilder
	 * @return void
	 */
	public function processFieldOptions(array $options, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		foreach ($options as $option) {
			$this->executeOption('alterQueryField', $option, $queryConfiguration, $queryBuilder);
		}
	}

	/**
	 * Processes an array of column-options by calling the contained alterQueryColumn()
	 * methods and passing a queryConfiguration reference and queryBuilder object to it.
	 *
	 * @param array $options
	 * @param array &$queryConfiguration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder $queryBuilder
	 * @return void
	 */
	public function processColumnOptions(array $options, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		foreach ($options as $option) {
			$this->executeOption('alterQueryColumn', $option, $queryConfiguration, $queryBuilder);
		}
	}

	/**
	 * Processes an array of row-options by calling the contained alterQueryRow()
	 * methods and passing a queryConfiguration reference and queryBuilder object to it.
	 *
	 * @param array $options
	 * @param array &$queryConfiguration
	 * @param \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder $queryBuilder
	 * @return void
	 */
	public function processRowOptions(array $options, array &$queryConfiguration, QueryBuilder $queryBuilder) {
		foreach ($options as $option) {
			$this->executeOption('alterQueryRow', $option, $queryConfiguration, $queryBuilder);
		}
	}

	/**
	 * {@inheritDoc}
	 * @return \Innologi\Decosdata\Service\Option\Query\OptionInterface
	 * @see \Innologi\Decosdata\Service\Option\OptionServiceAbstract::resolveOptionClass()
	 * @throws Exception\InvalidOptionClass
	 */
	protected function resolveOptionClass($className) {
		$object = parent::resolveOptionClass($className);
		if ( !($object instanceof Query\OptionInterface) ) {
			throw new Exception\InvalidOptionClass(1448552519, array(
				// since $object was retrieved via objectManager, we're not sure if $object Class === $className
				get_class($object), Query\OptionInterface::class
			));
		}
		return $object;
	}
}
