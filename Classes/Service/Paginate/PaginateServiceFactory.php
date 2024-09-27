<?php

namespace Innologi\Decosdata\Service\Paginate;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Exception\NotInitialized;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Pagination Service Factory
 *
 * This factory singleton is able to register multiple PaginateService instances.
 * You should always retrieve your desired PaginateService through this factory.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class PaginateServiceFactory implements SingletonInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $instances = [];

    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create / retrieve the PaginateService instance that is identified by its parameters
     *
     * @return PaginateService
     */
    public function get(array $parameters)
    {
        $id = \substr(\md5(\json_encode($parameters)), 0, 8);
        if (!isset($this->instances[$id])) {
            $this->instances[$id] = $this->objectManager->get(PaginateService::class, $id, $parameters);
        }
        return $this->instances[$id];
    }

    /**
     * Retrieve already existing instance
     *
     * @param string $id
     * @throws NotInitialized
     * @return PaginateService
     */
    public function getById($id)
    {
        if (!isset($this->instances[$id])) {
            throw new NotInitialized(1530549752, ['PaginateService[' . $id . ']']);
        }
        return $this->instances[$id];
    }
}
