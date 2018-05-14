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
use Innologi\TagBuilder\TagInterface;
use Innologi\TagBuilder\Tag;
/**
 * Adds Tag Attributes
 *
 * Adds tag attributes to the current Tag, or if TagContent, encloses it with
 * a new Tag with said attributes.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class AddTagAttributes implements OptionInterface {
	// @LOW ___allow this to be set via configuration? TS? or maybe even args?
	/**
	 * @var string
	 */
	protected $defaultTag = 'span';

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterTag()
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		if ( !(isset($args['attributes']) && is_array($args['attributes'])) ) {
			throw new MissingArgument(1466180012, [self::class, 'attributes']);
		}

		// if not an actual Tag, enclose it with one with all the attributes and return it
		if (!($tag instanceof Tag)) {
			return $service->getTagFactory()->createTag(
				$this->defaultTag, $args['attributes'], $tag
			);
		}

		// if $tag has class and $args has class and appendClass is set ..
		if (isset($args['appendClass']) && (bool) $args['appendClass']
			&& isset($args['attributes']['class'][0]) && $tag->hasAttribute('class')
		) {
			$tag->addAttributes(['class' => $tag->getAttribute('class') . ' ' . $args['attributes']['class']]);
			unset($args['attributes']['class']);
		}

		// otherwise, just add the remaining attributes
		$tag->addAttributes($args['attributes']);
		return $tag;
	}

}
