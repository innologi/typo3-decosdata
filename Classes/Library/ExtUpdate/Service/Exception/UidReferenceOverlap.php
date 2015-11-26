<?php
namespace Innologi\Decosdata\Library\ExtUpdate\Service\Exception;
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

/**
 * Uid Reference Overlap Exception
 *
 * @package InnologiLibs
 * @subpackage ExtUpdate
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class UidReferenceOverlap extends Exception {

	/**
	 * @var string
	 */
	protected $message = 'Automatic migration completely failed due to uid-reference-overlapping. You will have to start over completely by reverting a database/table backup, or remove all data and re-import all Decos data manually. Possible reason: imports were updated or TCA records were created before migration was complete. (Table: %1$s, Property: %2$s, Source: %3$s, Target: %4$s)';

}
