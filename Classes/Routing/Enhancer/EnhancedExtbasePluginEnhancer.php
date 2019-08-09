<?php
namespace Innologi\Decosdata\Routing\Enhancer;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Routing\ExtbasePluginEnhancer;
/**
 * Enhanced Extbase Plugin Enhancer
 *
 * Adds support for querystring-based mechanisms.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EnhancedExtbasePluginEnhancer extends ExtbasePluginEnhancer
{

	/**
	 * {@inheritdoc}
	 */
	public function buildResult(Route $route, array $results, array $remainingQueryParameters = []): PageArguments
	{
		$pageArguments = parent::buildResult($route, $results, $remainingQueryParameters);

		// detect match/resolve context, as opposed to generate
		if (isset($results['_route'])) {
			foreach ( $pageArguments->getRouteArguments() as $var => $val ) {
				if ($var === $this->namespace && \is_array($val)) {
					// fallback to support dependencies to uriBuilder's / typolink's addQueryString
						// although I can hardly contain my vomit for doing this, it at least gives me something to work with until TYPO3 routing matures.
						// note that this is dangerous as any other (custom) script may not behave well to our alterations.
						// So far, no adverse side-affects in vanilla TYPO3 in my use-cases. Even when cHash is involved, it works.
					$_GET[$var] = isset($_GET[$var]) ? \array_merge($_GET[$var], $val) : $val;
					if (isset($_SERVER['QUERY_STRING'][0])) {
						$currentQueryArray = [];
						parse_str($_SERVER['QUERY_STRING'], $currentQueryArray);
						ArrayUtility::mergeRecursiveWithOverrule($currentQueryArray, [$var => $val], true);
					} else {
						$currentQueryArray = [$var => $val];
					}
					$_SERVER['QUERY_STRING'] = HttpUtility::buildQueryString($currentQueryArray, '&');
					// set Utility's internal cache in case it was read before
					GeneralUtility::setIndpEnv('QUERY_STRING', $_SERVER['QUERY_STRING']);
					break;
				}
			}
		}

		return $pageArguments;
	}

}
