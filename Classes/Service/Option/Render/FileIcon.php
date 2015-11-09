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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use Innologi\Decosdata\Service\Option\RenderOptionService;
/**
 * File Icon option
 *
 * Renders a fileicon of the content, if content represents a valid file.
 * Uses the original icons used by the t3skin sprites.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FileIcon extends FileOptionAbstract {
	// @TODO ___Absolute URIs for other contexts than normal HTML?

	/**
	 * @var boolean
	 */
	protected $t3skinIsLoaded = FALSE;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->t3skinIsLoaded = ExtensionManagementUtility::isLoaded('t3skin');
	}

	/**
	 * {@inheritDoc}
	 * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
	 */
	public function alterContentValue(array $args, &$content, RenderOptionService $service) {
		if ( !($this->t3skinIsLoaded && $this->isFileHandle($service->getOriginalContent())) ) {
			return;
		}

		$file = $this->getFileObject($this->fileUid);
		$fileExtension = $file->getExtension();
		$mimeIconName = IconUtility::mapFileExtensionToSpriteIconName(
			$fileExtension
		);
		// note that a sprite icon name includes its original containing directory
		// we just need to replace the first '-' with '/'
		$iconName = join('/', explode('-', $mimeIconName, 2));

		// contains the original icons that make up the sprites
		$iconPath = ExtensionManagementUtility::siteRelPath('t3skin') . 'images/icons/';
		$iconExt = array('png','gif');

		// check if the icon exists with the given extensions
		$iconExists = FALSE;
		foreach ($iconExt as $ext) {
			$icon = $iconPath . $iconName . '.' . $ext;
			if (is_file(PATH_site . $icon)) {
				$content = $icon;
				$iconExists = TRUE;
				break;
			}
		}

		// if icon does not exist, return without alteration
		if (!$iconExists) {
			// @TODO ___why not set a default icon instead?
			return;
		}

		// change $content to icon image
			// none of these are useful to me if I don't add BE CSS
			//$content = IconUtility::getSpriteIconForResource($file);
			//$content = IconUtility::getSpriteIconForFile($file->getExtension());
			//$content = IconUtility::getSpriteIcon($mimeIconName);

		// @TODO ___the html tag should be configurable by TS or be provided by an internal TYPO3 function
		$content = sprintf(
			'<img src="%1$s" alt="%2$s" />',
			$content,
			$fileExtension
		);
	}

}
