<?php
namespace Innologi\Decosdata\Library\FalApi;
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
use TYPO3\CMS\Core\SingletonInterface;
/**
 * FileReference Domain Object factory
 *
 * You only need a Domain Object when you're going to persist a File as a reference
 * in the Parent Object. To persist correctly, you also need to add the following
 * TCA to the Parent Object's reference field:
 *
 * 'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
 *		'{fieldname}',
 *		array(
 *			'foreign_match_fields' => array(
 *				'fieldname' => '{fieldname}',
 *				'tablenames' => '{tablename}',
 *				'table_local' => 'sys_file',
 *			),
 *		)
 *	),
 *
 * @package InnologiLibs
 * @subpackage FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FileReferenceFactory implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 * @inject
	 */
	protected $resourceFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Creates and returns domain object from filepath.
	 *
	 * @param string filePath
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 * @throws Exception\FileException
	 */
	public function createByFilePath($filePath) {
		$fileObject = $this->resourceFactory->retrieveFileOrFolderObject($filePath);
		if ( !($fileObject instanceof \TYPO3\CMS\Core\Resource\File) ) {
			throw new Exception\FileException(1448550039, array($filePath));
		}
		return $this->create(array(
			'uid_local' => $fileObject->getUid()
		));
	}

	/**
	 * Creates and returns domain object from data.
	 *
	 * @param array $data
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 */
	public function create(array $data) {
		/* @var $object \TYPO3\CMS\Extbase\Domain\Model\FileReference */
		$object = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Model\\FileReference');
		$object->setOriginalResource(
			// all you really need is an 'uid_local' key with the File uid as value for it
			// to persist correctly. Below method will throw an exception if missing.
			$this->resourceFactory->createFileReferenceObject($data)
		);
		return $object;
	}

}