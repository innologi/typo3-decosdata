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

/**
 * Custom Tag object
 *
 * Represents a Tag. Can be interchangable with other TagInterface classes.
 * Tag will render itself and its content recursively into a string.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Tag extends \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder implements TagInterface {

	/**
	 * Content of the tag to be rendered
	 *
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
		// @LOW __note that this way, there is no htmlspecialchars checking.. I suppose we could still do so @ render? but then make sure there is no double htmlspecialchars()!
		$this->attributes = $attributes;
		$this->content = $content;
	}

	/**
	 * {@inheritDoc}
	 * @see \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::setTagName()
	 * @return $this
	 */
	public function setTagName($tagName) {
		$this->tagName = $tagName;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::forceClosingTag()
	 * @return $this
	 */
	public function forceClosingTag($forceClosingTag) {
		$this->forceClosingTag = $forceClosingTag;
		return $this;
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

	/**
	 * {@inheritDoc}
	 * @see TagInterface::setContent()
	 * @param TagInterface $content
	 */
	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::getContent()
	 * @return TagInterface
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::hasContent()
	 */
	public function hasContent() {
		return $this->content !== NULL;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::__toString()
	 */
	public function __toString() {
		return $this->render();
	}

}
