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
use Innologi\Decosdata\Domain\Repository\FieldRepository;
use Innologi\Decosdata\Exception\MissingObjectProperty;
use Innologi\Decosdata\Mvc\Domain\FactoryAbstract;

/**
 * Field factory
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FieldFactory extends FactoryAbstract
{
    /**
     * @var FieldRepository
     */
    protected $repository;

    public function injectRepository(FieldRepository $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * Sets properties of domain object
     *
     * @throws \Innologi\Decosdata\Exception\MissingObjectProperty
     */
    protected function setProperties(\Innologi\Decosdata\Domain\Model\Field $object, array $data)
    {
        if (!isset($data['field_name'][0])) {
            throw new MissingObjectProperty(1448549862, [
                'field_name',
                'Field',
            ]);
        }
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
     * @return \Innologi\Decosdata\Domain\Model\Field
     */
    public function getByFieldName($fieldName, $autoInsert = false)
    {
        $cacheKey = $fieldName . ';;;' . $this->storagePid;
        if (!isset($this->objectCache[$cacheKey])) {
            /** @var \Innologi\Decosdata\Domain\Model\Field $fieldObject */
            $fieldObject = $this->repository->findOneByFieldName($fieldName);
            if ($fieldObject === null) {
                $data = [
                    'field_name' => $fieldName,
                ];
                $fieldObject = $autoInsert
                    ? $this->createAndStoreObject($data)
                    : $this->create($data);
            }
            $this->objectCache[$cacheKey] = $fieldObject;
        }
        return $this->objectCache[$cacheKey];
    }
}
