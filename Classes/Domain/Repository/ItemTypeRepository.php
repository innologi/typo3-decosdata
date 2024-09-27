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
 * ItemType domain repository
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemTypeRepository extends RepositoryAbstract
{
    /**
     * Find by item_type value
     *
     * @param string $type
     * @return \Innologi\Decosdata\Domain\Model\ItemType|null
     */
    public function findOneByItemType($type)
    {
        $query = $this->createQuery();
        return $query->matching(
            $query->equals('item_type', $type),
        )->execute()->getFirst();
    }
}
