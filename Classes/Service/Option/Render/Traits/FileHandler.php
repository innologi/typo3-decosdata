<?php

namespace Innologi\Decosdata\Service\Option\Render\Traits;

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
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File Handler Trait
 *
 * Offers some basic file-related methods for use by RenderOptions.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait FileHandler
{
    use MockFileHandler;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var integer
     */
    protected $fileUid;

    public function getResourceFactory(): ResourceFactory
    {
        if ($this->resourceFactory === null) {
            $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        }
        return $this->resourceFactory;
    }

    /**
     * Run all the file handler checks and return either a File object or NULL
     *
     * @param string $content
     * @return \TYPO3\CMS\Core\Resource\File|null
     */
    protected function getFileObject($content)
    {
        $file = null;
        if ($this->isFileHandle($content)) {
            $file = $this->getFileObjectByUid($this->fileUid);
        } elseif ($this->isMockFileHandle($content)) {
            $file = $this->getMockFileObjectByPath($this->mockPath);
            $this->fileUid = 0;
        }
        return $file;
    }

    /**
     * Returns whether the argument is a file handle
     *
     * @param string $fileHandle
     * @return boolean
     */
    protected function isFileHandle($fileHandle)
    {
        if (str_starts_with($fileHandle, 'file:')) {
            $parts = explode(':', $fileHandle, 2);
            if (is_numeric($parts[1])) {
                $this->fileUid = (int) $parts[1];
                return true;
            }
        }
        return false;
    }

    /**
     * Returns File Object, or NULL if it fails.
     *
     * @param integer $fileUid
     * @return \TYPO3\CMS\Core\Resource\File|null
     */
    protected function getFileObjectByUid($fileUid)
    {
        try {
            return $this->getResourceFactory()->getFileObject($fileUid);
        } catch (FileDoesNotExistException) {
            // @TODO log this? or does it get logged internally already?
        }
        return null;
    }

    /**
     * Checks if a local directory exists. If it doesn't, it attempts to create it one
     * directory at a time.
     */
    protected function createDirectoryIfNotExists($dirPath)
    {
        if (is_dir($dirPath)) {
            return;
        }
        GeneralUtility::mkdir_deep($dirPath);
    }
}
