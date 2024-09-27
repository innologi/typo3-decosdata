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
use Innologi\TagBuilder\TagContent;
use Innologi\TagBuilder\TagInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File Size option
 *
 * Renders the filesize of the content, if content represents a valid file.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FileSize implements OptionInterface
{
    use Traits\FileHandler;

    /**
     * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
     */
    public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service)
    {
        if (($file = $this->getFileObject($service->getOriginalContent())) === null) {
            return $tag;
        }

        $content = GeneralUtility::formatSize(
            $file->getSize(),
            // @TODO ___get default format from typoscript?
            // @LOW ___support formatting argument?
            'b|kb|MB|GB|TB',
        );

        if ($tag instanceof TagContent) {
            return $tag->reset()->setContent($content);
        }

        return $service->getTagFactory()->createTagContent($content);
    }
}
