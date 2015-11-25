<?php
namespace Innologi\Decosdata\ViewHelpers;
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

/**
 * Render ViewHelper
 *
 * Improves upon the original RenderViewHelper by utilizing the RuntimeStorageService.
 * This improves performance when a partial or section is rendered multiple times with
 * the same arguments.
 *
 * Obvious use cases are Fluid's Pagination Widget, or decosdata's PageBrowser ViewHelper.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RenderViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\RenderViewHelper {

	/**
	 * @var \Innologi\Decosdata\Service\RuntimeStorageService
	 * @inject
	 */
	protected $storageService;

	/**
	 * Renders the content.
	 *
	 * @param string $section Name of section to render. If used in a layout, renders a section of the main content file. If used inside a standard template, renders a section of the same file.
	 * @param string $partial Reference to a partial.
	 * @param array $arguments Arguments to pass to the partial.
	 * @param boolean $optional Set to TRUE, to ignore unknown sections, so the definition of a section inside a template can be optional for a layout
	 * @return string
	 */
	public function render($section = NULL, $partial = NULL, $arguments = array(), $optional = FALSE) {
		$id = 'RenderViewHelper-' . $this->storageService->generateHash($this->arguments);
		if ($this->storageService->has($id)) {
			$output = $this->storageService->get($id);
		} else {
			$output = parent::render($section, $partial, $arguments, $optional);
			$this->storageService->set($id, $output);
		}
		return $output;
	}

}
