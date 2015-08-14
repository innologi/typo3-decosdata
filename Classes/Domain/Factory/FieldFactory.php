<?php
namespace Innologi\Decospublisher7\Domain\Factory;
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
use Innologi\Decospublisher7\Mvc\Domain\FactoryAbstract;
/**
 * Field factory
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FieldFactory extends FactoryAbstract {

	/**
	 * @var \Innologi\Decospublisher7\Domain\Repository\FieldRepository
	 * @inject
	 */
	protected $repository;

	/**
	 * Sets properties of domain object
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Field $object
	 * @param array $data
	 * @return void
	 */
	protected function setProperties(\Innologi\Decospublisher7\Domain\Model\Field $object, array $data) {
		// @LOW ___consider throwing an exception if field_name does not exist, same for other factories
		$object->setFieldName($data['field_name']);
	}

	/**
	 * Retrieve Field Object from, in this order until successful:
	 * - local object cache
	 * - repository
	 * - newly created by parameters
	 *
	 * Optionally inserts the (value)Object into the database
	 * to relieve the much heavier persistence mechanisms.
	 *
	 * @param string $fieldName
	 * @param boolean $autoInsert
	 * @return \Innologi\Decospublisher7\Domain\Model\Field
	 */
	public function getByFieldName($fieldName, $autoInsert = FALSE) {
		if (!isset($objectCache[$fieldName])) {
			/* @var $fieldObject \Innologi\Decospublisher7\Domain\Model\Field */
			$fieldObject = $this->repository->findOneByFieldName($fieldName);
			if ($fieldObject === NULL) {
				$data = array('field_name' => $fieldName);
				$fieldObject = $autoInsert
					? $this->createAndStoreObject($data)
					: $this->create($data);
			}
			$objectCache[$fieldName] = $fieldObject;
		}
		return $objectCache[$fieldName];
	}

}