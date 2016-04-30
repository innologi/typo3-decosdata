<?php
namespace Innologi\Decosdata\Service\Option\Render;
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
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
/**
 * Custom Image option
 *
 * Renders a custom image as the content.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CustomImage implements OptionInterface {
	// @TODO ___Absolute URIs for other contexts than normal HTML?

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, &$content, RenderOptionService $service) {
		if ( !isset($args['path'][0]) ) {
			// @TODO ___test this
			throw new MissingArgument(1462019242, array(self::class, 'path'));
		}

		// check requirements
		$ifContent = isset($args['requireContent']) && (bool)$args['requireContent'];
		$ifRelation = isset($args['requireRelation']) && (bool)$args['requireRelation'];
		$originalContent = $service->getOriginalContent();
		$item = $service->getItem();
		$index = $service->getIndex();
		if (
			($ifContent && !isset($originalContent[0]))
			|| ($ifRelation && !isset($item['relation' . $index]))
		) {
			// if requirements are set but not met, stop
			return;
		}

		if (!is_file(PATH_site . $args['path'])) {
			// @TODO ___throw exception instead
			// if image does not exist, stop
			return;
		}

		// @TODO ___the html tag should be configurable by TS or be provided by an internal TYPO3 function
		// @TODO ___alt???
		$content = sprintf(
			'<img src="%1$s" />',
			$args['path']
		);
	}

}
