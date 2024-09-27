<?php

namespace Innologi\Decosdata\Utility;

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
 * Debug Utility class
 *
 * Provides some static methods for debugging purposes.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DebugUtility
{
    // @TODO ___find any other use of the original DebugUtility, and replace it with this one
    /**
     * Formats array values on new lines in a single string
     *
     * @return string
     */
    public static function formatArrayValues(array $array)
    {
        $output = PHP_EOL . '- ' . join(', ' . PHP_EOL . '- ', $array);
        return $output;
    }

    /**
     * Formats array key => value pairs on new lines in a single string
     *
     * @return string
     */
    public static function formatArray(array $array)
    {
        $temp = [];
        foreach ($array as $key => $value) {
            $temp[] = $key . ' ' . PHP_EOL . $value;
        }
        $output = PHP_EOL . join(' ' . PHP_EOL . PHP_EOL, $temp);
        return $output;
    }
}
