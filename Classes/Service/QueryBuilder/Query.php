<?php
namespace Innologi\Decosdata\Service\QueryBuilder;
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
 * Query Object
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Query {

	/**
	 * @var string
	 */
	protected $query;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * Class constructor
	 *
	 * @param string $query
	 * @param array $parameters
	 * @return void
	 */
	public function __construct($query = '', array $parameters = array()) {
		$this->query = $query;
		$this->parameters = $parameters;
	}

	/**
	 * Return query
	 *
	 * @return string
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * Sets query
	 *
	 * @param string $query
	 * @return Query
	 */
	public function setQuery($query) {
		$this->query = $query;
		return $this;
	}

	/**
	 * Returns parameters
	 *
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Sets parameters
	 *
	 * @param array $parameters
	 * @return Query
	 */
	public function setParameters(array $parameters) {
		$this->parameters = $parameters;
		return $this;
	}

}
