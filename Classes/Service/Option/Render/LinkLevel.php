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
use Innologi\Decosdata\Library\TagBuilder\TagInterface;
use Innologi\Decosdata\Library\TagBuilder\TagContent;

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
	 * @var \Innologi\Decosdata\Service\ParameterService
	 * @inject
	 */
	protected $parameterService;

	// @LOW ___allow this to be set via configuration? TS? or maybe even args?
	/**
	 * @var string
	 */
	protected $defaultContent = 'link';

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		if ( !(isset($args['level']) && is_numeric($args['level'])) ) {
			throw new MissingArgument(1449048090, [self::class, 'level']);
		}
		$linkValue = NULL;
		$item = $service->getItem();
		if (isset($args['linkItem']) && $args['linkItem']) {
			$linkValue = (string) $item['id'] ?? '';
		} elseif (isset($args['linkRelation']) && $args['linkRelation']) {
			$linkValue = (string) $item['relation' . $service->getIndex()] ?? '';
		} else {
			$linkValue = (string) $item['id' . $service->getIndex()] ?? '';
			//$linkValue = $service->getOriginalContent();
		}

		// no linkValue means no uri building, this is not an error
		if (!isset($linkValue[0])) {
			return $tag;
		}

		// no content in a TagContent means we need a default substitute
		if ($tag instanceof TagContent && !$tag->hasContent()) {
			$tag->setContent($this->defaultContent);
		}

		// @LOW _if we just read the latest _ argument, can't we derive level from there, so we can get rid of the level arg? we have to be sure that it's not read anywhere else
		// @LOW _see if addQueryStringMethod is of any use to us
		$uri = $service->getControllerContext()->getUriBuilder()
			->reset()
			->setAddQueryString(TRUE)
			// @LOW _if we support a page argument per level, we could maintain current and previous levels through arguments. Another option would be the session
			->setArgumentsToBeExcludedFromQueryString([
				$this->parameterService->wrapInPluginNamespace('page'),
				$this->parameterService->wrapInPluginNamespace('search')
			])
			->uriFor(NULL, [
				// @LOW don't we want these details to reside in parameterService?
				'level' => $args['level'],
				'_' . $args['level'] => $this->parameterService->encodeParameter($linkValue)
			]);

		// @TODO ___title and or other attributes? in tx_decospublisher, a title could be set through an argument, which would expand the query to include the field containing the title
		return $service->getTagFactory()->createTag('a', ['href' => $uri], $tag);
	}

}
