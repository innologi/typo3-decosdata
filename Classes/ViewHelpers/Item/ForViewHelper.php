<?php
namespace Innologi\Decosdata\ViewHelpers\Item;
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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
// @TODO ___use \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface ?
/**
 * Item.For ViewHelper
 *
 * Loops through every content element (skipping other essential elements),
 * and passes the element and the applicable configuration to new variables.
 *
 * Since this is already a specialized decosdata item content VH and I don't
 * want to add another one, this VH also takes care of any contentpaging if
 * applicable.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ForViewHelper extends AbstractViewHelper {

	/**
	 * @var boolean
	 */
	protected $escapeOutput = FALSE;

	/**
	 * @var boolean
	 */
	protected $escapeChildren = FALSE;

	/**
	 * Initialize arguments
	 *
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerArgument('configuration', 'array', 'Configuration directives for rendering all content fields.', TRUE);
		$this->registerArgument('item', 'array', 'Item array containing all its content fields.', TRUE);
		$this->registerArgument('contentAs', 'string', 'Variable name for current content.', FALSE, 'content');
		$this->registerArgument('configAs', 'string', 'Variable name for current content configuration.', FALSE, 'contentConfiguration');
		$this->registerArgument('indexAs', 'string', 'Variable name for current content index.', FALSE, 'index');
		$this->registerArgument('offset', 'integer', 'Start index at', FALSE, 1);
		// for content paging
		$this->registerArgument('pagingPartial', 'string', 'Partial for rendering contentpaging', FALSE, 'ViewHelpers/PageBrowser');
		$this->registerArgument('includeXhrPagingResultCount', 'boolean', 'Includes resultcount for contentpaging', FALSE, TRUE);
	}

	/**
	 * Iterates through elements of $item and renders child nodes exclusively for content
	 * fields as directed by configuration.
	 *
	 * @return string
	 */
	public function render() {
		$item = $this->arguments['item'];
		$output = '';
		foreach ($this->arguments['configuration'] as $index => $config) {
			if ($index < $this->arguments['offset']) {
				continue;
			}
			if (!isset($item['content' . $index])) {
				// @TODO ___throw exception 'configuration / content mismatch'
				// @TODO ___wait, if we do that, don't we have an issue with BIS level 3 field 5?
			}
			$content = $item['content' . $index];
			$this->templateVariableContainer->add($this->arguments['indexAs'], $index);
			$this->templateVariableContainer->add($this->arguments['configAs'], $config);
			$this->templateVariableContainer->add(
				$this->arguments['contentAs'],
				// if content has its own paging, concatenate the paging
				isset($item['paging' . $index]['more']) && $item['paging' . $index]['more'] !== FALSE
					? $this->addContentPager($content, $item['paging' . $index])
					: $content
			);
			$output .= $this->renderChildren();
			$this->templateVariableContainer->remove($this->arguments['contentAs']);
			$this->templateVariableContainer->remove($this->arguments['configAs']);
			$this->templateVariableContainer->remove($this->arguments['indexAs']);
		}
		return $output;
	}

	/**
	 * Adds content-specific paging to the given content
	 *
	 * For content-specific paging, we ONLY support the XHR pager!
	 *
	 * @param string $content
	 * @param array $paging
	 * @return string
	 */
	protected function addContentPager($content, array $paging) {
		// we use the default pagebrowser VH partial for consistency
		return $this->viewHelperVariableContainer->getView()->renderPartial(
			$this->arguments['pagingPartial'],
			'contentpaging',
			[
				'nextPageArgs' => ['page' . $paging['id'] => $paging['page']+1],
				'xhrUri' => $paging['more'],
				'xhrAutoload' => $paging['autoload'] ? 1 : 0,
				'xhrTarget' => 'content',
				'resultCount' => $paging['total'],
				'includeXhrPagingResultCount' => $this->arguments['includeXhrPagingResultCount'],
				'content' => $content
			]
		);
	}

}
