<?php
namespace Innologi\Decosdata\Service;
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
 * Runtime Storage Service
 *
 * Offers a basic storage that is maintained during a single runtime,
 * like an unpersisted cache. Mostly useful when comparing hashed input
 * as identifier against this storage, and retrieving possibly earlier
 * associated and stored content.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RuntimeStorageService implements SingletonInterface {

	/**
	 * @var array
	 */
	protected $storage = array();


	/**
	 * Generates and returns hash for $input
	 *
	 * @param mixed $input
	 * @return string
	 */
	public function generateHash($input) {
		return md5(json_encode($input));
	}

	/**
	 * Checks if the $identifier is known
	 *
	 * @param string $identifier
	 * @return boolean
	 */
	public function has($identifier) {
		return isset($this->storage[$identifier]);
	}

	/**
	 * Retrieves variable by $identifier
	 *
	 * @param string $identifier
	 * @return mixed
	 */
	public function get($identifier) {
		return $this->storage[$identifier];
	}

	/**
	 * Sets variable by $identifier
	 *
	 * @param string $identifier
	 * @param mixed $variable
	 * @return $this
	 */
	public function set($identifier, $variable) {
		$this->storage[$identifier] = $variable;
		return $this;
	}

	/**
	 * Removes variable by $identifier
	 *
	 * @param string $identifier
	 * @return $this
	 */
	public function remove($identifier) {
		unset($this->storage[$identifier]);
		return $this;
	}

}
