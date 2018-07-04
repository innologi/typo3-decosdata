<?php
namespace Innologi\Decosdata\Service\Option\Render;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\TagBuilder\TagContent;
use Innologi\TagBuilder\TagInterface;
use Innologi\TagBuilder\Tag;
/**
 * Image Features
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImageFeatures implements OptionInterface {

	/**
	 * @var integer
	 */
	protected $number = 0;

	/**
	 * @var integer
	 */
	protected $contentIncrements = 0;

	/**
	 * @var integer
	 */
	protected $forcedTotal = 0;

	/**
	 * @var string
	 */
	protected $lastContentId;

	/**
	 * @var string
	 */
	protected $markOriginal = '###IMAGE-FEATURE-ORIGINAL###';

	/**
	 * @var \Innologi\TYPO3AssetProvider\ProviderServiceInterface
	 */
	protected $assetProviderService;

	/**
	 * Returns AssetProviderService
	 *
	 * @return \Innologi\TYPO3AssetProvider\ProviderServiceInterface
	 */
	protected function getAssetProviderService() {
		if ($this->assetProviderService === NULL) {
			$this->assetProviderService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				\TYPO3\CMS\Extbase\Object\ObjectManager::class
			)->get(
				\Innologi\TYPO3AssetProvider\ProviderServiceInterface::class
			);
		}
		return $this->assetProviderService;
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		$containerClass = 'image-container';
		if (isset($args['scale']['enable']) && (bool)$args['scale']['enable']) {
			$containerClass .= $this->scaleImage($args['scale'], $tag);
		}
		if (isset($args['number']['enable']) && (bool)$args['number']['enable']) {
			$tag = $this->numberImage($args['number'], $tag, $service);
		}
		if (isset($args['obfuscate']['enable']) && (bool)$args['obfuscate']['enable']) {
			$tag = $this->obfuscateImage($tag, $service);
		}

		return $service->getTagFactory()->createTag('div', ['class' => $containerClass], $tag);
	}

	/**
	 * Scales an image, by default by a factor of 2
	 *
	 * You can provide a different scale-factor, with different effects, for example:
	 * - >1.0: initially shrink the image so that it grows to its configured size on hover
	 * - <1.0: initially grow the image so that it shrinks to its configured size on hover
	 *
	 * Note that changes to the scale-factor need to be manually configured in CSS for
	 * class ".scale", in addition to passing the option argument
	 *
	 * @param array $configuration
	 * @param TagInterface $tag
	 * @return string
	 */
	protected function scaleImage(array $configuration, TagInterface $tag) {
		// @TODO currently requires $tag to be the img tag. but I'd like to support query(tagname.class subtag.class) in tagBuilder
		if (! ($tag instanceof Tag && $tag->getTagName() === 'img') ) {
			return '';
		}

		// @LOW once we support CSS variables, we could make the scale-factor known to CSS
		$factor = (float) ($configuration['factor'] ?? 2);
		$attributes = [];
		if ($tag->hasAttribute('width')) {
			$attributes['width'] = $tag->getAttribute('width') / $factor;
		}
		if ($tag->hasAttribute('height')) {
			$attributes['height'] = $tag->getAttribute('height') / $factor;
		}
		// overwrites existing attributes
		$tag->addAttributes($attributes);
		return ' scale';
		// @TODO if an image is 300px high, the image-container is 304px high, why is that?
		// if we fix that, we could set the css on .scale itself, which includes the below features
	}

	/**
	 * Numbers image
	 *
	 * @param array $configuration
	 * @param TagInterface $tag
	 * @return TagContent
	 */
	protected function numberImage(array $configuration, TagInterface $tag, RenderOptionService $service) {
		// @TODO what about normal paging? we could retrieve the general paging parameters to properly define the offset
		// through forceTotal, you can set the total increment to take place before the next item/content combination
			// this is especially useful if the numbered images may not be complete initially due to e.g. content-paging
		if (isset($configuration['forceTotal'])) {
			// casting index to int removes any index-modification by recursive options
			$id = $service->getItem()['id'] . '_' . (int) $service->getIndex();
			if ($id !== $this->lastContentId) {
				$this->number += ($this->forcedTotal - $this->contentIncrements);
				$this->lastContentId = $id;
				$this->contentIncrements = 0;
				$this->forcedTotal = (int) $configuration['forceTotal'];
			}
			$this->contentIncrements++;
		}

		$this->number++;
		$numberTag = $service->getTagFactory()->createTag(
			'span',
			['class' => 'image-number', 'data-number' => $this->number],
			$service->getTagFactory()->createTagContent((string) $this->number)
		);
		$mark = '###IMAGE-FEATURE-NUMBER###';
		if ($tag instanceof TagContent) {
			$tag->setContent($mark . $tag->getContent())->addMarkReplacements([$mark => $numberTag]);
		} else {
			$tag = $service->getTagFactory()->createTagContent(
				$mark . $this->markOriginal,
				[$mark => $numberTag, $this->markOriginal => $tag]
			);
		}
		return $tag;
	}

	/**
	 * Obfuscate image download
	 *
	 * @param TagInterface $tag
	 * @param RenderOptionService $service
	 * @return Tag
	 */
	protected function obfuscateImage(TagInterface $tag, RenderOptionService $service) {
		$mark = '###IMAGE-FEATURE-OBFUSCATE###';
		$obfuscateTag = $service->getTagFactory()->createTag('span', ['class' => 'obfuscate-layer'])->forceClosingTag(TRUE);
		if ($tag instanceof TagContent) {
			$tag->setContent($mark . $tag->getContent())->addMarkReplacements([$mark => $obfuscateTag]);
		} else {
			$tag = $service->getTagFactory()->createTagContent(
				$mark . $this->markOriginal,
				[$mark => $obfuscateTag, $this->markOriginal => $tag]
			);
		}

		return $service->getTagFactory()->createTag('span', ['class' => 'obfuscate'], $tag);
	}

}
