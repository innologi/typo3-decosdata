<?php

namespace Innologi\Decosdata\Service\Importer\StorageHandler;

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

/**
 * Importer Storage Handler interface
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface StorageHandlerInterface
{
    /**
     * Initialize Storage Handler
     *
     * This will allow the importer to set specific parameters
     * that are of importance.
     *
     * @param integer $pid
     */
    public function initialize($pid);

    /**
     * Push an item ready for commit.
     *
     * @return mixed
     * @throws \Innologi\Decosdata\Service\Importer\Exception\InvalidItem
     */
    public function pushItem(array $data);

    /**
     * Push an itemblob ready for commit.
     *
     * @throws \Innologi\Decosdata\Service\Importer\Exception\InvalidItemBlob
     */
    public function pushItemBlob(array $data);

    /**
     * Push an itemfield ready for commit.
     */
    public function pushItemField(array $data);

    /**
     * Commits all pushed data.
     */
    public function commit();
}
