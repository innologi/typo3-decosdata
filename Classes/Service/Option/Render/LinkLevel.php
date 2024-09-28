<?php

namespace Innologi\Decosdata\Service\Option\Render;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\ParameterService;
use Innologi\TagBuilder\TagContent;
use Innologi\TagBuilder\TagInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Link Level option
 *
 * Renders a link to the designated publication level.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class LinkLevel implements OptionInterface
{
    // @LOW ___allow this to be set via configuration? TS? or maybe even args?
    /**
     * @var string
     */
    protected $defaultContent = 'link';

    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ParameterService $parameterService,
    ) {}

    /**
     * @see \Innologi\Decosdata\Service\Option\Render\OptionInterface::alterContentValue()
     */
    public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service)
    {
        if (!(isset($args['level']) && is_numeric($args['level']))) {
            throw new MissingArgument(1449048090, [self::class, 'level']);
        }
        $linkValue = null;
        $item = $service->getItem();
        if (isset($args['linkItem']) && $args['linkItem']) {
            $linkValue = (string) $item['id'] ?? '';
        } elseif (isset($args['linkRelation']) && $args['linkRelation']) {
            $linkValue = (string) $item['relation' . $service->getIndex()] ?? '';
        } else {
            $linkValue = (string) $item['id' . $service->getIndex()] ?? '';
            //$linkValue = $service->getOriginalContent();
        }

        // no linkValue means no uri building, this is not an error
        if (!isset($linkValue[0])) {
            return $tag;
        }

        // no content in a TagContent means we need a default substitute
        if ($tag instanceof TagContent && !$tag->hasContent()) {
            $tag->setContent($this->defaultContent);
        }

        // @LOW _if we just read the latest _ argument, can't we derive level from there, so we can get rid of the level arg? we have to be sure that it's not read anywhere else
        $uri = $this->getUriBuilder($service)
            ->reset()
            ->uriFor(null, array_merge(
                $this->parameterService->getLevelParameters(),
                [
                    'level' => $args['level'],
                    '_' . $args['level'] => $this->parameterService->encodeParameter($linkValue),
                ],
            ));

        // @TODO ___title and or other attributes? in tx_decospublisher, a title could be set through an argument, which would expand the query to include the field containing the title
        return $service->getTagFactory()->createTag('a', [
            'href' => $uri,
        ], $tag);
    }

    protected function getUriBuilder(RenderOptionService $service): UriBuilder
    {
        return $this->uriBuilder->setRequest($service->getRequest());
    }
}
