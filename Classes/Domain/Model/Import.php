<?php
namespace Innologi\Decospublisher7\Domain\Model;
/***************************************************************
 *
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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
/**
 * Import
 */
class Import extends AbstractEntity {
	# @TODO go through all docs
	# @LOW chaining on setters? be consistent about it

	/**
	 * Name
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $name = '';

	/**
	 * Import file path
	 *
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 * @validate NotEmpty
	 */
	protected $filepath = NULL;

	/**
	 * File directory path for documents
	 *
	 * @var string
	 * @validate NotEmpty
	 */
	protected $documentpath = '';

	/**
	 * File Hash
	 *
	 * @var string
	 */
	protected $hash = '';

	/**
	 * Include in auto update?
	 *
	 * @var boolean
	 */
	protected $autoUpdate = FALSE;

	/**
	 * Forget all previous content on new update
	 *
	 * @var boolean
	 */
	protected $forgetOnUpdate = FALSE;

	/**
	 * Returns the name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the filepath
	 *
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $filepath
	 */
	public function getFilepath() {
		return $this->filepath;
	}

	/**
	 * Sets the filepath
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $filepath
	 * @return void
	 */
	public function setFilepath(\TYPO3\CMS\Extbase\Domain\Model\FileReference $filepath) {
		$this->filepath = $filepath;
	}

	/**
	 * Returns the documentpath
	 *
	 * @return string
	 */
	public function getDocumentpath() {
		return $this->documentpath;
	}

	/**
	 * Sets the documentpath
	 *
	 * @param string $documentpath
	 * @return void
	 */
	public function setDocumentpath($documentpath) {
		$this->documentpath = $documentpath;
	}

	/**
	 * Returns the hash
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * Sets the hash
	 *
	 * @param string $hash
	 * @return void
	 */
	public function setHash($hash) {
		$this->hash = $hash;
	}

	/**
	 * Returns the autoUpdate
	 *
	 * @return boolean
	 */
	public function getAutoUpdate() {
		return $this->autoUpdate;
	}

	/**
	 * Sets the autoUpdate
	 *
	 * @param boolean $autoUpdate
	 * @return void
	 */
	public function setAutoUpdate($autoUpdate) {
		$this->autoUpdate = $autoUpdate;
	}

	/**
	 * Returns the forgetOnUpdate
	 *
	 * @return boolean
	 */
	public function getForgetOnUpdate() {
		return $this->forgetOnUpdate;
	}

	/**
	 * Sets the forgetOnUpdate
	 *
	 * @param boolean $forgetOnUpdate
	 * @return void
	 */
	public function setForgetOnUpdate($forgetOnUpdate) {
		$this->forgetOnUpdate = $forgetOnUpdate;
	}

}