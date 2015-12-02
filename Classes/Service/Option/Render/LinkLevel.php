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
 * Link Level option
 *
 * Renders a link to the designated publication level.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class LinkLevel implements OptionInterface {

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, &$content, RenderOptionService $service) {
		if ( !(isset($args['level']) && is_int($args['level'])) ) {
			throw new MissingArgument(1449048090, array(self::class, 'level'));
		}
		$linkValue = $service->getOriginalContent();

		$uriBuilder = $service->getControllerContext()->getUriBuilder();
		// @TODO ___test if this should be urlencode, or rawurlencode, or.. whatever
		$uri = $uriBuilder->reset()->uriFor(NULL, array('level' => $args['level'], '_2' => rawurlencode($linkValue)));
		// @TODO ___title and or other parameters? in tx_decospublisher, a title could be set through an argument, which would expand the query to include the field containing the title
		$content = '<a href="' . $uri . '">' . $content . '</a>';
	}


}
