<?php
namespace Innologi\Decosdata\Service\Option\Render\Traits;
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
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
 /**
 * File Handler Trait
 *
 * Offers some basic file-related methods for use by RenderOptions.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait FileHandler {

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 * @inject
	 */
	protected $resourceFactory;

	/**
	 * @var integer
	 */
	protected $fileUid;

	/**
	 * Returns whether the argument is a file handle
	 *
	 * @param string $fileHandle
	 * @return boolean
	 */
	protected function isFileHandle($fileHandle) {
		if (strpos($fileHandle, 'file:') === 0) {
			$parts = explode(':', $fileHandle, 2);
			if (is_numeric($parts[1])) {
				$this->fileUid = (int) $parts[1];
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Returns File Object, or NULL if it fails.
	 *
	 * @param integer $fileUid
	 * @return \TYPO3\CMS\Core\Resource\File|NULL
	 */
	protected function getFileObject($fileUid) {
		try {
			return $this->resourceFactory->getFileObject($fileUid);
		} catch (FileDoesNotExistException $e) {
			// @TODO log this? or does it get logged internally already?
		}
		return NULL;
	}

}
