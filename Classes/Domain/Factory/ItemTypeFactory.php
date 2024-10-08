<?php

namespace Innologi\Decosdata\Domain\Factory;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Domain\Repository\ItemTypeRepository;
use Innologi\Decosdata\Exception\MissingObjectProperty;
use Innologi\Decosdata\Mvc\Domain\FactoryAbstract;

/**
 * ItemType factory
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemTypeFactory extends FactoryAbstract
{
    /**
     * @var ItemTypeRepository
     */
    protected $repository;

    public function injectRepository(ItemTypeRepository $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * Sets properties of domain object
     *
     * @throws \Innologi\Decosdata\Exception\MissingObjectProperty
     */
    protected function setProperties(\Innologi\Decosdata\Domain\Model\ItemType $object, array $data)
    {
        if (!isset($data['item_type'][0])) {
            throw new MissingObjectProperty(1448549941, [
                'item_type',
                'ItemType',
            ]);
        }
        $object->setItemType($data['item_type']);
    }

    /**
     * Retrieve ItemType Object from, in this order until successful:
     * - local object cache
     * - repository
     * - newly created by parameters
     *
     * Optionally inserts the (value)Object into the database
     * to relieve the much heavier persistence mechanisms.
     *
     * @param string $type
     * @param boolean $autoInsert
     * @return \Innologi\Decosdata\Domain\Model\ItemType
     */
    public function getByItemType($type, $autoInsert = false)
    {
        $cacheKey = $type . ';;;' . $this->storagePid;
        if (!isset($this->objectCache[$cacheKey])) {
            /** @var \Innologi\Decosdata\Domain\Model\ItemType $typeObject */
            $typeObject = $this->repository->findOneByItemType($type);
            if ($typeObject === null) {
                $data = [
                    'item_type' => $type,
                ];
                $typeObject = $autoInsert
                    ? $this->createAndStoreObject($data)
                    : $this->create($data);
            }
            $this->objectCache[$cacheKey] = $typeObject;
        }
        return $this->objectCache[$cacheKey];
    }
}
