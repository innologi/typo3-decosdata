<?php
namespace Innologi\Decosdata\Service\Option\Render\Traits;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\RenderOptionService;
/**
 * Item Access Trait
 *
 * Contains methods and properties used to process item-accessing option arguments.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
trait ItemAccess {

	/**
	 * @var string
	 */
	protected $pattern = '^\{item:([a-zA-Z0-9]+)\}$';

	/**
	 *
	 * @param string $argValue
	 * @param RenderOptionService $service
	 * @return string
	 */
	protected function itemAccess($argValue, RenderOptionService $service) {
		$match = [];
		if ( !(is_string($argValue) && preg_match('/' . $this->pattern . '/', $argValue, $match)) ) {
			return $argValue;
		}

		$item = $service->getItem();
		if (!isset($item[$match[1]])) {
			// @TODO throw exception if the field requested does not exist
		}
		return $item[$match[1]];
	}

}
