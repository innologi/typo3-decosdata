<?php
namespace Innologi\Decosdata\Service\Option\Render;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2016 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\TagBuilder\TagInterface;
/**
 * Render Option Interface
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
interface OptionInterface {
	// @TODO _______better doc
	/**
	 * Alters content.
	 *
	 * @param array $args
	 * @param \Innologi\Decosdata\Service\TagBuilder\TagInterface $tag
	 * @param \Innologi\Decosdata\Service\Option\RenderOptionService $service
	 * @return \Innologi\Decosdata\Service\TagBuilder\TagInterface
	 */
	public function alterContentValue(array $args, TagInterface $tag, RenderOptionService $service);

}
