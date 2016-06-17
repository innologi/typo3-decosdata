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
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use Innologi\Decosdata\Service\Option\RenderOptionService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
/**
 * Tag Builder
 *
 * Should be used to build Tag objects that are used to render
 * frontend content output.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class TagBuilder {
	// @LOW ___is there any upside to making this a singleton? Consider that many views probably use it twice (to produce a header part)
	// @TODO ___so, now we have a RenderVH, which calls the tagBuilder, which calls the RenderOptionService, from which RenderOptions call the tagBuilder to call the controllerContext ..
		// doesn't add up, does it? Why would the tagBuilder need to know about all the other stuff? Functionally, it shouldn't!

	/**
	 * @var \Innologi\Decosdata\Service\Option\RenderOptionService
	 */
	protected $optionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * Inject OptionService
	 *
	 * @param \Innologi\Decosdata\Service\Option\RenderOptionService $optionService
	 * @return void
	 */
	public function injectOptionService(RenderOptionService $optionService) {
		$this->optionService = $optionService;
		// @TODO _______this is not necessary if TagBuilder can be a singleton
		$optionService->setTagBuilder($this);
	}

	/**
	 * Sets controller context
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
	 * @return $this
	 */
	public function setControllerContext(ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
		return $this;
	}

	/**
	 * Returns controller context
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	public function getControllerContext() {
		return $this->controllerContext;
	}

	// @TODO ________________doc
	public function buildTag($content, array $renderOptions, $index, array $item) {
		$tag = $this->generateTagContent($content);
		if (!empty($renderOptions)) {
			// @TODO ___if we want to support field- en blob-less content, consider a render->content element referring to a content-# or a default 'current'. We would also have to have access to other content fields.
			$tag = $this->optionService->processOptions(
				$renderOptions,
				$tag,
				$index,
				$item
			);
		}
		return $tag;
	}

	/**
	 *
	 * @param string $tagName
	 * @param array $tagAttributes
	 * @param TagInterface $content
	 * @return Tag
	 */
	public function generateTag($tagName, array $tagAttributes = array(), TagInterface $content = NULL) {
		return GeneralUtility::makeInstance(Tag::class, $tagName, $tagAttributes, $content);
	}

	/**
	 *
	 * @param string $content
	 * return TagContent
	 */
	public function generateTagContent($content) {
		return GeneralUtility::makeInstance(TagContent::class, $content);
	}

}