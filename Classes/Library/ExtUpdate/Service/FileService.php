<?php
namespace Innologi\Decosdata\Library\ExtUpdate\Service;
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
/**
 * Ext Update Database Service
 *
 * Provides several file methods for common use-cases in ext-update context.
 * Note that it must be instantiated with the ObjectManager!
 *
 * @package InnologiLibs
 * @subpackage ExtUpdate
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FileService implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 * @inject
	 */
	protected $resourceFactory;

	/**
	 * @var \Innologi\Decosdata\Library\FalApi\FileReferenceRepository
	 * @inject
	 */
	protected $fileReferenceRepository;

	/**
	 * Language labels => messages
	 *
	 * @var array
	 */
	protected $lang = array(
		'notExist' => 'The file \'<code>%1$s</code>\' does not exist.',
		'notDocRoot' => 'The file \'<code>%1$s</code>\' lives outside of document root \'<code>%2$s</code>\'.'
	);

	/**
	 * Retrieve an existing FAL file object, or create a new one if
	 * it doesn't exist and return it.
	 *
	 * @param string $path
	 * @return \TYPO3\CMS\Core\Resource\File
	 * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
	 * @throws Exception\NotInDocumentRoot
	 */
	public function retrieveFileObjectByPath($path) {
		if ( !is_file($path) || !file_exists($path) ) {
			throw new FileDoesNotExistException(
				sprintf($this->lang['notExist'], $path)
			);
		}
		if (strpos($path, PATH_site) !== 0) {
			throw new Exception\NotInDocumentRoot(
				sprintf($this->lang['notDocRoot'], $path, PATH_site)
			);
		}
		// this method creates the record if one does not yet exist
		return $this->resourceFactory->retrieveFileOrFolderObject($path);
	}

	/**
	 * Sets a new file reference
	 *
	 * @param integer $fileUid
	 * @param string $foreignTable
	 * @param integer $foreignUid
	 * @param string $foreignField
	 * @param integer $pid
	 * @return void
	 */
	public function setFileReference($fileUid, $foreignTable, $foreignUid, $foreignField, $pid) {
		$this->fileReferenceRepository->setStoragePid($pid);
		$this->fileReferenceRepository->addRecord($fileUid, $foreignTable, $foreignUid, $foreignField);
	}

	/**
	 * Returns a File Object.
	 *
	 * @param integer $uid
	 * @return \TYPO3\CMS\Core\Resource\File
	 */
	public function getFileObjectByUid($uid) {
		return $this->resourceFactory->getFileObject($uid);
	}

	/**
	 * Returns default FAL storage object.
	 *
	 * @return \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	public function getDefaultStorage() {
		return $this->resourceFactory->getDefaultStorage();
	}

}
