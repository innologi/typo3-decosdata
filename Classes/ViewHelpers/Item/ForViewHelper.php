<?php
namespace Innologi\Decosdata\ViewHelpers\Item;
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
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception;
// @TODO ___use \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface ?
/**
 * Item.For ViewHelper
 *
 * Loops through every content element (skipping other essential elements),
 * and passes the element and the applicable configuration to new variables.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ForViewHelper extends AbstractViewHelper {

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->registerArgument('configuration', 'array', 'Configuration directives for rendering all content fields.', TRUE);
		$this->registerArgument('item', 'array', 'Item array containing all its content fields.', TRUE);
		$this->registerArgument('contentAs', 'string', 'Variable name for current content.', TRUE);
		$this->registerArgument('configAs', 'string', 'Variable name for current content configuration.', TRUE);
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
			if (!isset($item['content' . $index])) {
				// @TODO ___throw exception 'configuration / content mismatch'
			}
			$this->templateVariableContainer->add($this->arguments['configAs'], $config);
			$this->templateVariableContainer->add($this->arguments['contentAs'], $item['content' . $index]);
			$output .= $this->renderChildren();
			$this->templateVariableContainer->remove($this->arguments['contentAs']);
			$this->templateVariableContainer->remove($this->arguments['configAs']);
		}
		return $output;
	}

}
