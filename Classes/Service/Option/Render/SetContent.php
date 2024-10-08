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
use Innologi\Decosdata\Service\Option\Exception\MissingArgument;
use Innologi\Decosdata\Service\Option\RenderOptionService;
use Innologi\TagBuilder\TagContent;
use Innologi\TagBuilder\TagInterface;

/**
 * Set Content
 *
 * Replaces the content value.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SetContent implements OptionInterface
{
    /**
     * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
     * @throws \Innologi\Decosdata\Service\Option\Exception\MissingArgument
     */
    public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service)
    {
        $wrap = [];
        if (!isset($args['content'][0])) {
            throw new MissingArgument(1515607288, [self::class, 'content']);
        }

        if ($tag instanceof TagContent) {
            $tag->reset();
            $tag->setContent($args['content']);
            return $tag;
        }

        // if $tag is an actual Tag instance, replace its content object (if any)
        return $tag->setContent(
            $service->getTagFactory()->createTagContent($args['content']),
        );
    }
}
