<?php
namespace Innologi\Decosdata\Domain\Model;
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
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
/**
 * Import domain model
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Import extends AbstractEntity {

	/**
	 * Title
	 *
	 * @var string
	 * @extensionScannerIgnoreLine
	 * @validate NotEmpty
	 * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
	 */
	protected $title;

	/**
	 * File reference
	 *
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 * @extensionScannerIgnoreLine
	 * @validate NotEmpty
	 * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
	 */
	protected $file;

	/**
	 * File Hash
	 *
	 * @var string
	 */
	protected $hash;

	/**
	 * Forget all previous content on new update
	 *
	 * @var boolean
	 */
	protected $forgetOnUpdate;

	/**
	 * Returns the title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the title
	 *
	 * @param string $title
	 * @return \Innologi\Decosdata\Domain\Model\Import
	 */
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Returns the file
	 *
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference $file
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Sets the file
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $file
	 * @return \Innologi\Decosdata\Domain\Model\Import
	 */
	public function setFile(\TYPO3\CMS\Extbase\Domain\Model\FileReference $file) {
		$this->file = $file;
		return $this;
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
	 * @return \Innologi\Decosdata\Domain\Model\Import
	 */
	public function setHash($hash) {
		$this->hash = $hash;
		return $this;
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
	 * @return \Innologi\Decosdata\Domain\Model\Import
	 */
	public function setForgetOnUpdate($forgetOnUpdate) {
		$this->forgetOnUpdate = $forgetOnUpdate;
		return $this;
	}

}