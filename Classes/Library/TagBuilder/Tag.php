<?php
namespace Innologi\Decosdata\Library\TagBuilder;
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

/**
 * Custom Tag object
 *
 * Represents a Tag. Can be interchangable with other TagInterface classes.
 * Tag will render itself and its content recursively into a string.
 *
 * Inspired by \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder
 *
 * @package InnologiLibs
 * @subpackage TagBuilder
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Tag extends TagAbstract {

	/**
	 * @var string
	 */
	protected $tagName = '';

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var boolean
	 */
	protected $forceClosingTag = FALSE;

	/**
	 * @var TagInterface
	 */
	protected $content = NULL;

	/**
	 * Constructor
	 *
	 * @param string $tagName name of the tag to be rendered
	 * @param array $attributes
	 * @param TagInterface $content content of the tag to be rendered
	 * @return void
	 */
	public function __construct($tagName = '', array $attributes = [], TagInterface $content = NULL) {
		$this->tagName = $tagName;
		$this->attributes = $attributes;
		$this->content = $content;
	}

	/**
	 * Sets tag name
	 *
	 * @param string $tagName
	 * @return $this
	 */
	public function setTagName($tagName) {
		$this->tagName = $tagName;
		return $this;
	}

	/**
	 * Gets tag name
	 *
	 * @return string
	 */
	public function getTagName() {
		return $this->tagName;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::hasContent()
	 */
	public function hasContent() {
		return $this->content !== NULL;
	}

	/**
	 * Set this to TRUE to force a closing tag
	 * E.g. <textarea> cant be self-closing even if its empty
	 *
	 * @param boolean $forceClosingTag
	 * @return $this
	 */
	public function forceClosingTag($forceClosingTag) {
		$this->forceClosingTag = $forceClosingTag;
		return $this;
	}

	/**
	 * Returns TRUE if attribute exists
	 *
	 * @param string $attribute
	 * @return boolean
	 */
	public function hasAttribute($attribute) {
		return isset($this->attributes[$attribute]);
	}

	/**
	 * Get a single attribute
	 *
	 * @param string $attribute
	 * @return string
	 */
	public function getAttribute($attribute) {
		if (!$this->hasAttribute($attribute)) {
			return NULL;
		}
		return $this->attributes[$attribute];
	}

	/**
	 * Get all attributes
	 *
	 * @return array
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * Adds a single attribute
	 *
	 * @param string $attribute
	 * @param string $value
	 * @return $this
	 */
	public function addAttributes(array $attributes) {
		$this->attributes = $attributes + $this->attributes;
		return $this;
	}

	/**
	 * Removes an attribute
	 *
	 * @param string $attribute
	 * @return $this
	 */
	public function removeAttribute($attribute) {
		unset($this->attributes[$attribute]);
		return $this;
	}


	/**
	 * Renders and returns the tag
	 *
	 * @return string
	 */
	public function render() {
		if (!isset($this->tagName[0])) {
			return '';
		}
		$output = '<' . $this->tagName;
		foreach ($this->attributes as $attribute => $value) {
			$output .= ' ' . $attribute . '="' . htmlspecialchars($value) . '"';
		}
		if ($this->hasContent() || $this->forceClosingTag) {
			$output .= '>' . ($this->content ?? '') . '</' . $this->tagName . '>';
		} else {
			$output .= ' />';
		}
		return $output;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::reset()
	 */
	public function reset() {
		$this->tagName = '';
		$this->content = NULL;
		$this->attributes = [];
		$this->forceClosingTag = FALSE;
		return $this;
	}

}
