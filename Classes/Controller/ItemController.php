<?php
namespace Innologi\Decosdata\Controller;
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
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
/**
 * Item controller
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ItemController extends ActionController {

	/**
	 * @var \Innologi\Decosdata\Domain\Repository\ItemRepository
	 * @inject
	 */
	protected $itemRepository;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder
	 * @inject
	 */
	protected $queryBuilder;

	/**
	 * @var integer
	 */
	protected $level = 1;

	/**
	 * @var array
	 */
	protected $pluginConfiguration;

	/**
	 * {@inheritDoc}
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
	 */
	public function initializeAction() {
		$this->initializePluginConfiguration();
		$this->initializeSharedArguments();
	}

	/**
	 * Initializes plugin configuration for use by any action method
	 *
	 * @return void
	 */
	protected function initializePluginConfiguration() {
		$this->pluginConfiguration = array(
			'import' => array(
				2
			),
			'level' => array(
				1 => array(
					'paginate' => array(
						'pageLimit' => 30,
						'perPageLimit' => 50,
					),
					'itemType' => array(
						2
					),
					'contentField' => array(
						1 => array(
							'title' => 'Naam Regelgeving',
							'content' => array(
								array(
									'field' => 16,
								)
							),
							'order' => array(
								'sort' => 'ASC',
								'priority' => 10
							)
						),
						2 => array(
							'title' => 'Datum Inwerkingtreding',
							'content' => array(
								array(
									'field' => 23,
									'queryOptions' => array(
										array(
											'option' => 'DateConversion',
											'args' => array(
												'format' => '%d-%m-%Y'
											)
										),
										array(
											'option' => 'FilterItems',
											'args' => array(
												'filters' => array(
													array(
														'value' => 'NULL',
														'operator' => 'IS NOT',
													),
													array(
														'value' => 'NOW()',
														'operator' => '<=',
													)
												),
												'matchAll' => TRUE
											)
										),
									),
								)
							),
							'order' => array(
								'sort' => 'DESC',
								'priority' => 20
							)
						),
						3 => array(
							'title' => 'Datum Intrekking',
							'content' => array(
								array(
									'field' => 19,
									'queryOptions' => array(
										array(
											'option' => 'DateConversion',
											'args' => array(
												'format' => '%d-%m-%Y'
											)
										),
										array(
											'option' => 'FilterItems',
											'args' => array(
												'filters' => array(
													array(
														'value' => 'NULL',
														'operator' => 'IS',
													),
													array(
														'value' => 'NOW()',
														'operator' => '>',
													)
												)
											)
										)
									)
								)
							)
						),
						4 => array(
							'title' => 'Download',
							'content' => array(
								array(
									'blob' => TRUE,
								)
							),
							'renderOptions' => array(
								array(
									'option' => 'FileIcon',
								),
								array(
								 'option' => 'Wrapper',
									'args' => array(
										'wrap' => '|| {render:FileSize}|'
									)
								),
								array(
									'option' => 'FileDownload',
								)
							)
						)
					)
				)
			)
		);
	}

	/**
	 * Initializes request parameters shared by all actions
	 *
	 * @return void
	 */
	protected function initializeSharedArguments() {
		if ($this->request->hasArgument('page')) {
			// a valid page-parameter will set current page in configuration
			$this->pluginConfiguration['level'][$this->level]['paginate']['currentPage'] = (int) $this->request->getArgument('page');
		}
	}

	/**
	 * List items per publication configuration.
	 *
	 * @return void
	 */
	public function listAction() {
		// @TODO ___will probably require some validation in initializeSharedArguments() to see if provided level exists in available configuration
		$activeConfiguration = $this->pluginConfiguration['level'][$this->level];
		$items = $this->itemRepository->findWithStatement(
			($statement = $this->queryBuilder->buildListQuery(
				$activeConfiguration, $this->pluginConfiguration['import']
			)->createStatement())
		);

		$this->view->assign('configuration', $activeConfiguration);
		$this->view->assign('items', $items);
		// @TODO ___remove
		$this->view->assign('query', $statement->getQuery());
	}
}
