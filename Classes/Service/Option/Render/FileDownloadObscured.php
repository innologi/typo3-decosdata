<?php

namespace Innologi\Decosdata\Service\Option\Render;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\DownloadService;
use Innologi\Decosdata\Service\Option\Exception\MockFileUnsupported;
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\TagBuilder\TagInterface;

/**
 * File Download option
 *
 * Renders a download link of the content, if content represents a valid file.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FileDownloadObscured implements OptionInterface
{
    use Traits\FileHandler;
    // @TODO ___add class?
    /**
     * @var DownloadService
     */
    protected $downloadService;

    public function injectDownloadService(DownloadService $downloadService)
    {
        $this->downloadService = $downloadService;
    }

    /**
     * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
     */
    public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service)
    {
        // @TODO ___what if the content is empty? Can (and should) we differentiate between originalContent and content? I mean it's clear we shouldn't generate a downloadlink if no file was found
        if (($file = $this->getFileObject($service->getOriginalContent())) === null) {
            return $tag;
        }

        if (!($this->fileUid > 0)) {
            throw new MockFileUnsupported(1537371733, ['FileDownloadObscured']);
        }

        $item = $service->getItem();
        $id = 'id' . $service->getIndex();
        if (!isset($item['id'])) {
            // @TODO throw exception
        }

        return $service->getTagFactory()->createTag('a', [
            'href' => $this->downloadService->getDownloadUrl($this->fileUid, (int) $item[$id], $item['id']),
            'title' => $file->getName(),
        ], $tag);
    }
}
