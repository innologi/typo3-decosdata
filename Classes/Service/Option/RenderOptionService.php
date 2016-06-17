<?php
namespace Innologi\Decosdata\Service\Option;
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
use Innologi\Decosdata\Service\TagBuilder\TagBuilder;
/**
 * Render Option Service
 *
 * Handles the resolving and calling of option class/methods for use by RenderViewHelper
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RenderOptionService extends OptionServiceAbstract {

	/**
	 * @var \Innologi\Decosdata\Service\TagBuilder\TagBuilder
	 */
	protected $tagBuilder;

	/**
	 * @var string
	 */
	protected $originalContent;

	/**
	 * @var array
	 */
	protected $item;

	/**
	 * Matches argument:"value"[,]
	 * @var string
	 */
	protected $patternArgumentInline = '([a-zA-Z]+):"([^"]+)",?';

	/**
	 * Matches {render:RenderOption[( $patternArgumentInline[ ... ] )]}
	 * Note that %1$s needs to be replaced with $patternArgumentInline
	 * @var string
	 */
	protected $patternInline = '{render:([a-zA-Z]+)(\(((%1$s)*)\))?}';

	/**
	 * Public class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		// PHP < 5.6 does not support concatenation in above variable declarations, hence:
		$this->patternInline = sprintf($this->patternInline, $this->patternArgumentInline);
	}

	/**
	 * Returns tag builder
	 *
	 * @return \Innologi\Decosdata\Service\TagBuilder\TagBuilder
	 */
	public function getTagBuilder() {
		return $this->tagBuilder;
	}

	/**
	 * Sets tag builder
	 *
	 * @param \Innologi\Decosdata\Service\TagBuilder\TagBuilder $tagBuilder
	 * @return $this
	 */
	public function setTagBuilder(TagBuilder $tagBuilder) {
		$this->tagBuilder = $tagBuilder;
		return $this;
	}

	/**
	 * Returns original content value
	 *
	 * @return string
	 */
	public function getOriginalContent() {
		return $this->originalContent;
	}

	/**
	 * Returns the whole item array
	 *
	 * @return array
	 */
	public function getItem() {
		return $this->item;
	}

	/**
	 * Checks for, and then processes inline RenderOptions in $string.
	 * Returns the $string with all those inline RenderOptions replaced
	 * by marks, and corresponding values in $return.
	 *
	 * If an inline RenderOption is formatted incorrectly, it will
	 * not be replaced.
	 *
	 * @param string $string
	 * @param array &$return
	 * @return string
	 */
	public function processInlineOptions($string, array &$return) {
		// quick check to prevent an unnecessary performance impact by RegExp
		if (strpos($string, '{render:') === FALSE) {
			return $string;
		}

		$originalTag = $this->tagBuilder->generateTagContent($this->originalContent);

		// replaces inline RenderOptions by their resulting values
		return preg_replace_callback(
			'/' . $this->patternInline . '/',
			// callback function that executes the options
			function ($matches) use ($originalTag, &$return) {
				$option = array(
					'option' => $matches[1],
					'args' => array()
				);
				// if arguments were found, they need to be detected by another regular expression,
				// as preg_replace_callback can't set multiple arguments in $matches[5] and $matches[6]
				// @LOW ___is there really no way? something I can change in the pattern used by preg_replace_callback?
				if (isset($matches[3][0])) {
					$argMatch = array();
					preg_match_all('/' . $this->patternArgumentInline . '/', $matches[3], $argMatch);
					if (isset($argMatch[1]) && isset($argMatch[2])) {
						foreach ($argMatch[1] as $index => $arg) {
							$option['args'][$arg] = $argMatch[2][$index];
						}
					}
				}
				// note that it will reset original content to the same value,
				// so until we support utilizing a different content value, no harm is done
				// @LOW __note that this does not yet cache entries that are set multiple times
				$mark = '###RENDER' . md5(json_encode($option)) . '###';
				$return[$mark] = $this->processOptions(array($option), $originalTag, $this->index, $this->item);

				return $mark;
			},
			$string
		);
	}

	/**
	 * Processes an array of render-options by calling the contained alterValue()
	 * methods and passing the content reference and renderer object to it.
	 *
	 * @param array $options
	 * @param string &$content
	 * $param integer $index
	 * @param array $item
	 * @return void
	 */
	public function processOptions(array $options, &$content, $index, array $item) {
		$this->item = $item;
		$this->index = $index;
		$this->originalContent = $content;
		foreach ($options as $option) {
			$this->executeOption('alterContentValue', $option, $content);
		}
	}

}