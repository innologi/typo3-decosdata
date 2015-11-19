<?php
namespace Innologi\Decosdata\Service\Option\Render;
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
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
/**
 * Wrapper
 *
 * Wraps content, in a similar way to stdWrap.noTrimWrap.
 * Example wrap argument:
 * - | Hello | Friend?|
 *
 * Also supports inline RenderOptions, i.e.:
 * - || {render:RenderOption(argument1:"value",argument2:"value")}|
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Wrapper implements OptionInterface {
	// @TODO ___do we really need a noTrimWrap equivalent? If we settle for a normal wrapping equivalent, we could likely do without the preg_match()
	/**
	 * @var string
	 */
	protected $patternWrap = '^\|(.*)\|(.*)\|$';

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 * @throws \Innologi\Decosdata\Service\Option\Exception\MissingArgument
	 */
	public function alterContentValue(array $args, &$content, RenderOptionService $service) {
		$wrap = array();
		if (!isset($args['wrap'][0])) {
			throw new MissingArgument(1448551326, array(self::class, 'wrap'));
		}
		$args['wrap'] = $service->processInlineOptions($args['wrap']);
		// note that by choosing to first process inline options, they can never return
		// pipe characters without consequence to the following preg match
		if (!preg_match('/' . $this->patternWrap . '/', $args['wrap'], $wrap)) {
			// @TODO ___throw exception ..unless we do away with the preg_match?
		}
		$content = $wrap[1] . $content . $wrap[2];
	}

}