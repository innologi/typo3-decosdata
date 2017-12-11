<?php
namespace Innologi\Decosdata\Library\AssetProvider;
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
 * TYPO3 Extbase Asset Provider Service
 *
 * @package InnologiLibs
 * @subpackage AssetProvider
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ProviderService extends ProviderServiceAbstract {

	/**
	 * @LOW why not dynamically? Controller->Request can provide it
	 * @var string
	 */
	protected $extensionTsKey = 'tx_decosdata.';

	/**
	 * @var array
	 */
	protected $assetRegister = [];

	/**
	 * Provide assets based on Extbase arguments
	 *
	 * @param string $controllerName
	 * @param string $actionName
	 * @return void
	 */
	public function provideAssets($controllerName, $actionName) {
		if (isset($this->assetRegister[$controllerName][$action])) {
			// already provided these assets
			return;
		}


		if (isset($this->configuration['controller'][$controllerName])) {
			$configuration = array_merge(
				$this->configuration['default'],
				$this->configuration['controller'][$controllerName]['default'] ?? [],
				$this->configuration['controller'][$controllerName]['action'][$actionName] ?? []
			);
			$typoscript = array_merge(
				$this->typoscript['default.'],
				$this->typoscript['controller.'][$controllerName . '.']['default.'] ?? [],
				$this->typoscript['controller.'][$controllerName . '.']['action.'][$actionName . '.'] ?? []
			);
		} else {
			$configuration = $this->configuration['default'];
			$typoscript = $this->typoscript['default.'];
		}

		$this->runAssetProviders($configuration, $typoscript);


		if (!isset($this->assetRegister[$controllerName])) {
			$this->assetRegister[$controllerName] = [];
		}
		$this->assetRegister[$controllerName][$actionName] = TRUE;
	}

	/**
	 * Processes configuration on available asset providers
	 *
	 * @param array $configuration
	 * @param array $typoscript
	 * @return void
	 */
	protected function runAssetProviders(array $configuration, array $typoscript) {
		foreach ($configuration as $type => $conf) {
			// e.g. JavascriptProvider, CssProvider
			$className = ucfirst($type) . 'Provider';
			/* @var $assetProvider Provider\ProviderInterface */
			$assetProvider = $this->objectManager->get(__NAMESPACE__ . '\\Provider\\' . $className);
			$assetProvider->processConfiguration($conf, $typoscript[$type . '.']);
		}
	}

}