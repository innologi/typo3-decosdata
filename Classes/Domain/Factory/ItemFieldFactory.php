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
 * ItemField factory
 *
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemFieldFactory extends FactoryAbstract {

	/**
	 * Sets properties of domain object
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\ItemField $object
	 * @param array $data
	 * @return void
	 */
	protected function setProperties(\Innologi\Decospublisher7\Domain\Model\ItemField $object, array $data) {
		if (isset($data['item'])) {
			$object->setItem($data['item']);
		}
		if (isset($data['field'])) {
			$object->setField($data['field']);
		}
		if (isset($data['field_value'])) {
			$object->setFieldValue($data['field_value']);
		}
	}

}