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
use Innologi\TagBuilder\TagInterface;
use TYPO3\CMS\Core\Resource\AbstractFile;
/**
 * Image File option
 *
 * Renders a file as an Image.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImageFile implements OptionInterface {
	use Traits\FileHandler;
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
		if ( ($file = $this->getFileObject($service->getOriginalContent())) === NULL || !$this->isSupportedFile($file) ) {
			// if file could not be retrieved or is not of type image, either fall back to a set defaultFile or just return the tag
			if ( !isset($args['defaultFile']) || ($file = $this->getFileObject($args['defaultFile'])) === NULL || !$this->isSupportedFile($file) ) {
				return $tag;
			}
		}

		$processingInstructions = [
			'width' => $args['width'] ?? NULL,
			'height' => $args['height'] ?? NULL,
			// frame is for multi-page images (e.g. pdf) to determine the index of the page
			'frame' => $args['frame'] ?? 0,
		];
		$processedImage = $this->imageService->applyProcessingInstructions($file, $processingInstructions);
		$imageUri = $this->imageService->getImageUri($processedImage);

		$attributes = [
			'src' => $imageUri,
			'class' => 'file-' . $this->fileUid
		];
		if (isset($args['alt'][0]) && is_string($args['alt'])) {
			$attributes['alt'] = $args['alt'];
		}
		$attributes['width'] = $processedImage->getProperty('width');
		$attributes['height'] = $processedImage->getProperty('height');

		return $service->getTagFactory()->createTag('img', $attributes);
	}

	/**
	 * Return whether we support this file, i.e. an existing image or PDF
	 *
	 * @param AbstractFile $file
	 * @return boolean
	 */
	protected function isSupportedFile(AbstractFile $file) {
		return $file->exists() && (
			$file->getType() === AbstractFile::FILETYPE_IMAGE || strpos($file->getMimeType(), '/pdf') !== FALSE
		);
	}

}
