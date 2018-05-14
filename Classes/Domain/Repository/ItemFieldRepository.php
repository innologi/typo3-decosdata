<?php
namespace Innologi\Decosdata\Domain\Repository;
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
use Innologi\Decosdata\Mvc\Domain\RepositoryAbstract;
/**
 * ItemField domain repository
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemFieldRepository extends RepositoryAbstract {

	/**
	 * Finds one itemfield by its Field and its parent, a unique combination per item.
	 *
	 * @param \Innologi\Decosdata\Domain\Model\Field $field
	 * @param \Innologi\Decosdata\Domain\Model\Item $item
	 * @return \Innologi\Decosdata\Domain\Model\ItemField|NULL
	 */
	public function findOneByFieldAndItem(\Innologi\Decosdata\Domain\Model\Field $field, \Innologi\Decosdata\Domain\Model\Item $item) {
		$query = $this->createQuery();
		return $query->matching(
			$query->logicalAnd([
				$query->equals('field', $field),
				$query->equals('item', $item)
			])
		)->execute()->getFirst();
	}

}