<?php
namespace Innologi\Decosdata\Service\Option\Render;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Library\TagBuilder\TagInterface;
/**
 * Image File option
 *
 * Renders a file as an Image.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImageFile extends FileOptionAbstract {
	use Traits\ItemAccess;
	// @TODO ___Absolute URIs for other contexts than normal HTML?

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ImageService
	 * @inject
	 */
	protected $imageService;

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 * @see \TYPO3\CMS\Fluid\ViewHelpers\ImageViewHelper
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		if ( !$this->isFileHandle($service->getOriginalContent()) || ($file = $this->getFileObject($this->fileUid)) === NULL) {
			return $tag;
		}
		// @LOW no check on whether it really is an image?
		$processingInstructions = [
			'width' => $args['width'] ?? NULL,
			'height' => $args['height'] ?? NULL,
		];
		$processedImage = $this->imageService->applyProcessingInstructions($file, $processingInstructions);
		$imageUri = $this->imageService->getImageUri($processedImage);

		$attributes = [
			'src' => $imageUri,
			'class' => 'file-' . $this->fileUid
		];
		if (isset($args['alt'][0])) {
			$attributes['alt'] = $this->itemAccess($args['alt'], $service);
		}

		return $service->getTagFactory()->createTag('img', $attributes);
	}

}
