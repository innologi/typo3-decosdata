<?php
namespace Innologi\Decosdata\Service\Option\Render;
/****************************************************************
 * Copyright notice
 *
 * (c) 2018 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
use Innologi\TagBuilder\TagContent;
use Innologi\TagBuilder\TagInterface;
/**
 * Text 2 HTML
 *
 * Converts TagContent's content with from plain text to HTML.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Text2Html implements OptionInterface {

	/**
	 * {@inheritdoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		if (!$tag instanceof TagContent) {
			// only works on TagContent
			return $tag;
		}

		// if links is not explicitly disabled..
		if (!(isset($args['links']['disable']) && ((bool) $args['links']['disable']) !== FALSE)) {
			// if attributes given, make sure it's as an array
			if (isset($args['links']['attributes']) && !is_array($args['links']['attributes'])) {
				throw new MissingArgument(
					1527171449,
					[self::class, 'links.attributes'],
					'Query Option Configuration Error: %1$s optional argument \'%2$s\' needs to be an array.'
				);
			}
			$attributes = $args['links']['attributes'] ?? [];

			// replace any text prepended with http(s):// up until the first whitespace character
			\preg_replace_callback(
				';https?://\S+;',
				function ($match) use ($tag, $attributes, $service) {
					// @LOW shouldn't we detect multiples of the same URL?
					$tag->addMarkReplacements([
						$match[0] => $service->getTagFactory()->createTag(
							'a',
							['href' => $match[0]] + $attributes
						)->setContent(
							$service->getTagFactory()->createTagContent($match[0])
						)
					]);
				},
				$tag->getContent()
			);
		}

		// if nl2br is not explicitly disabled..
		if (! (isset($args['nl2br']['disable']) && ((bool) $args['nl2br']['disable']) !== FALSE) ) {
			$tag->setContent(
				\nl2br($tag->getContent())
			);
		}

		return $tag;
	}
}
