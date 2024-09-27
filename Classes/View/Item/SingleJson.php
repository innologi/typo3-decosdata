<?php

namespace Innologi\Decosdata\View\Item;

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

/**
 * Single JSON view
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SingleJson extends \TYPO3\CMS\Extbase\Mvc\View\JsonView
{
    /**
     * @var array
     */
    protected $variablesToRender = ['section'];

    /**
     * @var array
     */
    protected $configuration = [
        'section' => [
            '_only' => ['type', 'data', 'paging'],
        ],
    ];

    /**
     * Limits section.data to contentfields
     *
     * @param integer $contentFieldCount
     */
    public function addContentFieldsToConfiguration($contentFieldCount)
    {
        $this->configuration['section']['data'] = [
            '_descendAll' => [
                '_only' => ['id'],
            ],
        ];

        // we only really need the content fields, not other query-added fields that really pad the JSON size
        for ($i = 1; $i <= $contentFieldCount; $i++) {
            $this->configuration['section']['data']['_descendAll']['_only'][] = 'content' . $i;
        }
    }
}
