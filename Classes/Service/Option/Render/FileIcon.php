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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Innologi\Decosdata\Library\TagBuilder\TagInterface;
use Innologi\Decosdata\Library\TagBuilder\TagContent;
/**
 * File Icon option
 *
 * Renders a fileicon of the content, if content represents a valid file.
 * Uses the internal TYPO3 icons.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FileIcon extends FileOptionAbstract {
	// @TODO ___Absolute URIs for other contexts than normal HTML?

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service) {
		if ( !$this->isFileHandle($service->getOriginalContent()) ) {
			return;
		}

		$file = $this->getFileObject($this->fileUid);
		$fileExtension = $file->getExtension();

		// @LOW support setting the size through config?
		// will always return an icon, even if the extension is unknown
		// while technically a tag, we have to make do with a string
			// if we want to make use of the internal API
		$content = $this->iconFactory->getIconForFileExtension(
			$fileExtension, Icon::SIZE_SMALL
		)->getMarkup();
		if ($tag instanceof TagContent) {
			return $tag->reset()->setContent($content);
		}

		return $service->getTagFactory()->createTagContent($content);
	}

}
