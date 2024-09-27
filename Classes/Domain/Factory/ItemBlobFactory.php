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
use Innologi\Decosdata\Domain\Repository\ItemBlobRepository;
use Innologi\Decosdata\Exception\MissingObjectProperty;
use Innologi\Decosdata\Mvc\Domain\FactoryAbstract;
use Innologi\TYPO3FalApi\FileReferenceFactory;

/**
 * ItemBlob factory
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemBlobFactory extends FactoryAbstract
{
    /**
     * @var ItemBlobRepository
     */
    protected $repository;

    /**
     * @var FileReferenceFactory
     */
    protected $fileReferenceFactory;

    public function injectRepository(ItemBlobRepository $repository)
    {
        $this->repository = $repository;
    }

    public function injectFileReferenceFactory(FileReferenceFactory $fileReferenceFactory)
    {
        $this->fileReferenceFactory = $fileReferenceFactory;
    }

    /**
     * Sets properties of domain object
     *
     * @throws \Innologi\Decosdata\Exception\MissingObjectProperty
     */
    protected function setProperties(\Innologi\Decosdata\Domain\Model\ItemBlob $object, array $data)
    {
        if (!isset($data['item_key'][0])) {
            throw new MissingObjectProperty(1448549890, [
                'item_key',
                'ItemBlob',
            ]);
        }
        $object->setItemKey($data['item_key']);

        // data-to-be-converted
        if (isset($data['filepath'])) {
            $data['file'] = $this->fileReferenceFactory->createByFilePath($data['filepath']);
        }

        // regular data
        if (isset($data['sequence'])) {
            // LOW _____don't we rely on sequence?
            $object->setSequence($data['sequence']);
        }
        if (isset($data['file'])) {
            // @LOW _____if itemBlob already exists, it will have a file reference. If the filepath differs from the new one, should we remove the old file if it doesn't have any other file references?
            $object->setFile($data['file']);
        }
        if (isset($data['item'])) {
            $object->setItem($data['item']);
        }
    }

    /**
     * Retrieve ItemBlob Object from, in this order until successful:
     * - repository, values replaced by optional data parameters
     * - newly created by optional data parameters
     *
     * @param string $itemKey
     * @return \Innologi\Decosdata\Domain\Model\ItemBlob
     */
    public function getByItemKey($itemKey, array $data = [])
    {
        /** @var \Innologi\Decosdata\Domain\Model\ItemBlob $itemBlob */
        $itemBlob = $this->repository->findOneByItemKey($itemKey);
        if ($itemBlob === null) {
            if (empty($data)) {
                // set required parameters
                $data['item_key'] = $itemKey;
            }
            $itemBlob = $this->create($data);
        } elseif (!empty($data)) {
            // would be useless if no additional $data was given, as itemKey is already set
            $this->setProperties($itemBlob, $data);
        }
        return $itemBlob;
    }
}
