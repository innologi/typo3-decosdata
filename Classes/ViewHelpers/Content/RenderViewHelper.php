<?php
namespace Innologi\Decosdata\ViewHelpers\Content;
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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
// @TODO ___use \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface ?
/**
 * Content.Render ViewHelper
 *
 * Renders content as directed by configuration.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RenderViewHelper extends AbstractViewHelper {

	/**
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @var \Innologi\Decosdata\Service\TagBuilder\TagBuilder
	 * @inject
	 */
	protected $tagBuilder;

	// @TODO _______use method initializeArguments() instead
	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->registerArgument('tag', 'string', 'Wraps content with optional tag.', FALSE);
		$this->registerArgument('tagAttributes', 'array', 'Sets attributes to optional tag.', FALSE, []);

		$this->registerArgument('options', 'array', 'Configuration directives for rendering content.', FALSE, []);
		// @FIX ______does this stay FALSE + default or what? If yes, then how to deal with LinkLevel's dependency thereof?
		$this->registerArgument('item', 'array', 'Complete item.', FALSE, []);
		$this->registerArgument('index', 'integer', 'Current content index.', TRUE);
	}

	/**
	 * {@inheritDoc}
	 * @see \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::initialize()
	 */
	public function initialize() {
		if ($this->controllerContext !== NULL) {
			$this->tagBuilder->setControllerContext($this->controllerContext);
		}
	}

	/**
	 * Renders content
	 *
	 * @param string $content
	 * @return string
	 */
	public function render($content = NULL) {
		if ($content === NULL) {
			$content = trim($this->renderChildren());
		}

		// if configured, add an outer tag
		if ($this->hasArgument('tag')) {
			// we use the AddTag RenderOption instead of directly using the tagBuilder,
				// so that other options can be used to influence said tag, like adding
				// a class or title or data-attribute, or whatever
			$this->arguments['options'][] = [
				'option' => 'AddTag',
				'args' => [
					'name' => $this->arguments['tag'],
					'attributes' => $this->arguments['tagAttributes']
				]
			];
		}

		$tag = $this->tagBuilder->buildTag(
			$content,
			$this->arguments['options'],
			$this->arguments['index'],
			$this->arguments['item']
		);

		return $tag->render();
	}

}
