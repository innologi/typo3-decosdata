<?php
namespace Innologi\Decosdata\Service\TagBuilder;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
// @TODO ______doc
/**
 * Tag Interface
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface TagInterface {

	/**
	 * Returns TRUE if tag contains content, otherwise FALSE
	 *
	 * @return boolean
	 */
	public function hasContent();

	/**
	 * Gets the content of the tag
	 *
	 * @return mixed
	 */
	public function getContent();

	/**
	 * Sets the content of the tag
	 *
	 * @param mixed $content Content of the tag to be rendered
	 * @return $this
	 */
	public function setContent($content);


	/**
	 * Render the object as string, includes recursively rendering any content.
	 *
	 * @return string
	 */
	public function render();

	/**
	 * Magic method invoked when using the object directly into a string.
	 *
	 * @return string
	 */
	public function __toString();

	/**
	 * Resets objects properties.
	 *
	 * @return $this
	 */
	public function reset();

}