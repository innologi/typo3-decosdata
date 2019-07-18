<?php
namespace Innologi\Decosdata\Service\Option;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Paginate\PaginateService;
use Innologi\TagBuilder\TagFactory;
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
	 * @var TagFactory
	 */
	protected $tagFactory;

	/**
	 * @var \Innologi\Decosdata\Service\ConditionService
	 */
	protected $conditionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @var string
	 */
	protected $originalContent;

	/**
	 * @var array
	 */
	protected $item;

	/**
	 * @var \Innologi\Decosdata\Service\Paginate\PaginateService
	 */
	protected $paginator;

	/**
	 * Matches argument:"value"[,]
	 * @var string
	 */
	protected $patternArgumentInline = '([a-zA-Z0-9]+):"([^"]+)",?';

	/**
	 * Matches {render:RenderOption[( $patternArgumentInline[ ... ] )]}
	 * Note that %1$s needs to be replaced with $patternArgumentInline
	 * @var string
	 */
	protected $patternInline = '{render:([a-zA-Z]+)(\(((%1$s)*)\))?}';

	/**
	 *
	 * @param TagFactory $tagFactory
	 * @return void
	 */
	public function injectTagFactory(TagFactory $tagFactory)
	{
		$this->tagFactory = $tagFactory;
	}

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
	 * Injects ConditionService and sets a reference to this RenderOptionService
	 *
	 * @param \Innologi\Decosdata\Service\ConditionService $conditionService
	 * @return void
	 */
	public function injectConditionService(\Innologi\Decosdata\Service\ConditionService $conditionService) {
		$conditionService->setRenderOptionService($this);
		$this->conditionService = $conditionService;
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

	/**
	 * Returns tag builder
	 *
	 * @return \Innologi\TagBuilder\TagFactory
	 */
	public function getTagFactory() {
		return $this->tagFactory;
	}

	/**
	 * Returns relevant paginator
	 *
	 * @return \Innologi\Decosdata\Service\Paginate\PaginateService|NULL
	 */
	public function getPaginator() {
		return $this->paginator;
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
	 * @return array
	 */
	public function processInlineOptions($string) {
		// quick check to prevent an unnecessary performance impact by RegExp
		if (strpos($string, '{render:') === FALSE) {
			return [];
		}

		$replacements = [];
		$matches = [];
		if (preg_match_all('/' . $this->patternInline . '/', $string, $matches) === FALSE) {
			// @TODO ____throw exception?
		}

		foreach ($matches[0] as $index => $match) {
			$option = [
				'option' => $matches[1][$index],
				'args' => []
			];
			// if arguments were found, they need to be indentified by another regular expression,
			// as preg_match_all can't set multiple arguments in $matches[5] and $matches[6]
			// @LOW ___is there really no way? something I can change in the pattern used by preg_match_all?
			if (isset($matches[3][$index][0])) {
				$argMatch = [];
				preg_match_all('/' . $this->patternArgumentInline . '/', $matches[3][$index], $argMatch);
				if (isset($argMatch[1]) && isset($argMatch[2])) {
					foreach ($argMatch[1] as $argIndex => $arg) {
						$option['args'][$arg] = $argMatch[2][$argIndex];
					}
				}
			}
			// @LOW __note that this does not yet cache entries that are set multiple times
			$replacements[$match] = $this->processOptions([ $option ], $this->originalContent, $this->index . 'in', $this->item);
		}

		return $replacements;
	}

	/**
	 * Processes an array of render-options by calling the contained alterValue()
	 * methods and passing the content reference and renderer object to it.
	 *
	 * @param array $options
	 * @param string $content
	 * $param string $index
	 * @param array $item
	 * @param PaginateService $paginator
	 * @return \Innologi\TagBuilder\TagInterface
	 */
	public function processOptions(array $options, $content, $index, array $item, PaginateService $paginator = NULL) {
		// safeguard original values for recursion before overwrite
		$previously = [$this->item, $this->index, $this->originalContent, $this->paginator];
		$this->item = $item;
		$this->index = (string)$index;
		$this->originalContent = $content;

		// make item results accessible for option arg.var mechanism
		$this->optionVariables['item'] = $item;

		// starting TagInterface instance
		$tag = $this->tagFactory->createTagContent($content);

		$lastOptions = [];
		foreach ($options as $optionIndex => $option) {
			if (isset($option['if']) && is_array($option['if']) && !$this->conditionService->ifMatch($option['if'], $optionIndex, $index)) {
				// skip if there is an if conf that isn't matched
				continue;
			}
			// if an option has the last attribute, save it for last
			if (isset($option['last']) && (bool)$option['last']) {
				$lastOptions[] = $option;
				continue;
			}
			// @LOW I would prefer forcing the method to exist through OptionInterface and throw a NotSupported exception from the abstract
				// this would require me to change the RenderOption classes to extend the abstract instead, just as how QueryOptions work
			// if the option is set to paginate and supports it, prepare pagination
			$this->paginator = NULL;
			if ($paginator !== NULL && isset($option['paginate']) && (bool) $option['paginate'] && \method_exists($this->getOptionObject($option), 'paginateIterate')) {
				$this->paginator = $paginator->setCallback(
					[$this->getOptionObject($option), 'paginateIterate'],
					// args can contain resolved vars through executeOption, so pass as reference
					[&$option['args'], $this]
				);
			}

			$tag = $this->executeOption('alterContentValue', $option, $tag);
		}
		// last options, if any
		foreach ($lastOptions as $option) {
			$tag = $this->executeOption('alterContentValue', $option, $tag);
		}

		// restore safeguarded original values
		list($this->item, $this->index, $this->originalContent, $this->paginator) = $previously;
		$this->optionVariables['item'] = $this->item;
		return $tag;
	}

}