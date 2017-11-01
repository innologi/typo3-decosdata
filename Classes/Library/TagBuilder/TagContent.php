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
 * Custom TagContent object
 *
 * Represents content of a tag. Normally, this is a string, and can be interchangable with
 * other TagInterface classes. TagContent can additionaly contain multiple other
 * TagInterfaces within $markReplacements, that will be rendered into $content where $content
 * has corresponding marks.
 *
 * @package InnologiLibs
 * @subpackage TagBuilder
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TagContent extends TagAbstract {

	/**
	 * @var string
	 */
	protected $content = '';

	/**
	 * @var array
	 */
	protected $markReplacements = [];

	/**
	 * Constructor
	 *
	 * @param string $content
	 * @return void
	 */
	public function __construct($content = '', array $markReplacements = []) {
		$this->content = $content;
		$this->markReplacements = $markReplacements;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::hasContent()
	 */
	public function hasContent() {
		return isset($this->content[0]);
	}

	/**
	 * Adds mark replacements for content
	 *
	 * @param array $markReplacements
	 * @return $this
	 */
	public function addMarkReplacements(array $markReplacements) {
		$this->markReplacements = $markReplacements + $this->markReplacements;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::render()
	 */
	public function render() {
		$content = $this->content;
		/** @var TagInterface $tag */
		foreach ($this->markReplacements as $mark => $tag) {
			$content = str_replace($mark, $tag->render(), $content);
		}
		return $content;
	}

	/**
	 * {@inheritDoc}
	 * @see TagInterface::reset()
	 */
	public function reset() {
		$this->content = '';
		$this->markReplacements = [];
		return $this;
	}

}
