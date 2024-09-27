<?php

namespace Innologi\Decosdata\Service\Option\Render\Traits;

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
use Innologi\TYPO3FalApi\MockFileFactory;

/**
 * Mock File Handler Trait
 *
 * Offers some basic file-related methods for use by RenderOptions
 * that help imitate File objects for files not persisted in FAL.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait MockFileHandler
{
    /**
     * @var MockFileFactory
     */
    protected $mockFileFactory;

    /**
     * @var string
     */
    protected $mockPath;

    public function injectMockFileFactory(MockFileFactory $mockFileFactory)
    {
        $this->mockFileFactory = $mockFileFactory;
    }

    /**
     * Returns whether the argument is a mockfile handle
     *
     * @param string $mockFileHandle
     * @return boolean
     */
    protected function isMockFileHandle($mockFileHandle)
    {
        if (str_starts_with($mockFileHandle, 'mockfile:')) {
            $parts = explode(':', $mockFileHandle, 2);
            if (is_file($parts[1])) {
                $this->mockPath = $parts[1];
                return true;
            }
        }
        return false;
    }

    /**
     * Return MockFile object by filepath
     *
     * @param string $filePath
     * @return \Innologi\TYPO3FalApi\MockFile
     */
    protected function getMockFileObjectByPath($filePath)
    {
        return $this->mockFileFactory->getByFilePath($filePath);
    }
}
