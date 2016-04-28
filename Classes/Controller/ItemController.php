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
		$pid = (int) $GLOBALS['TSFE']->id;
		// @LOW _consider this: we translated querybuilder configuration from arrays to classes. would there be an advantage to doing the same for this? this isn't going to be adjustable like query configurations, but maybe there are other advantages?
		// extdev: regelgeving actief
		if ($pid === 11) {
		$this->pluginConfiguration = array(
			'import' => array(
				4
			),
			'level' => array(
				1 => array(
					'paginate' => array(
						'pageLimit' => 30,
						'perPageLimit' => 50,
					),
					'itemType' => array(
						// DOCUMENT
						1
					),
					'contentField' => array(
						1 => array(
							'title' => 'Naam Regelgeving',
							'content' => array(
								array(
									// TEXT9
									'field' => 6,
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
									// DATE5
									'field' => 34,
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
														'value' => '',
														'operator' => '!=',
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
									// DATE6
									'field' => 31,
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
														'value' => '',
														'operator' => '=',
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
		} elseif ($pid === 18) {
		// extdev: BIS
		$this->pluginConfiguration = array(
			'import' => array(
				5,1
			),
			'level' => array(
				1 => array(
					'paginate' => array(
						'pageLimit' => 20,
						'perPageLimit' => 20,
					),
					'itemType' => array(
						// FOLDER
						2
					),
					// @TODO ___temporary solution, until I know how I'm going to replace filterView and childView options from tx_decospublisher
					'noItemId' => TRUE,
					'contentField' => array(
						1 => array(
							'title' => 'Vergaderingen',
							'content' => array(
								array(
									// SUBJECT1
									'field' => 23,
								)
							),
							'renderOptions' => array(
								array(
									'option' => 'LinkLevel',
									'args' => array(
										'level' => 2,
									)
								)
							),
							'order' => array(
								'sort' => 'ASC',
								'priority' => 10
							)
						)
					),
					'queryOptions' => array(
						array(
							'option' => 'FilterItems',
							'args' => array(
								'filters' => array(
									array(
										'value' => 'vergaderdossiers',
										'operator' => '=',
										// BOOKNAME
										'field' => 2
									),
									array(
										'value' => '1',
										'operator' => '=',
										// BOL3
										'field' => 16
									)
								),
								'matchAll' => TRUE
							)
						)
					)
				),
				2 => array(
					'paginate' => array(
						'type' => 'yearly',
						'pageLimit' => 20,
						// DATE1
						'field' => 24
					),
					'itemType' => array(
						// FOLDER
						2
					),
					'contentField' => array(
						1 => array(
							'title' => 'Vergaderdatum',
							'content' => array(
								array(
									// DATE1
									'field' => 24,
									'order' => array(
										'sort' => 'DESC',
										'priority' => 10
									),
									'queryOptions' => array(
										array(
											'option' => 'DateConversion',
											'args' => array(
												'format' => '%d-%m-%Y'
											)
										)
									)
								)
							),
							'renderOptions' => array(
								array(
									'option' => 'LinkLevel',
									'args' => array(
										'level' => 3,
										'linkItem' => TRUE
									)
								)
							),
						)
					),
					'queryOptions' => array(
						array(
							'option' => 'FilterItems',
							'args' => array(
								'filters' => array(
									// perfect replacement for filterView! using what is already there :D
									array(
										'parameter' => '_2',
										'operator' => '=',
										// SUBJECT1
										'field' => 23
									),
									array(
										'value' => 'vergaderdossiers',
										'operator' => '=',
										// BOOKNAME
										'field' => 2
									),
									array(
										'value' => '1',
										'operator' => '=',
										// BOL3
										'field' => 16
									)
								),
								'matchAll' => TRUE
							)
						)
					)
				),
				// @FIX ______multiple views per plugin?
				// @FIX ______different view types per view? we have list, now we need single for "header"?
				// @FIX ______zaak support
				// @FIX ______crumbpaths
				// 1:4(1,1,1|1,1,2|1,1,3|1,*,*);
				// 1:fixNumberOrdering();;
				3 => array(
					'paginate' => array(
						'pageLimit' => 20,
						'perPageLimit' => 10,
					),
					'itemType' => array(
						// DOCUMENT
						1
					),
					'contentField' => array(
						1 => array(
							'title' => 'Agendanr.',
							'content' => array(
								/*array(
									// DATE2 (NUM2)
									'field' => 32,
								),
								array(
									// DATE3 (NUM3)
									'field' => 35,
								),
								array(
									// DATE4 (NUM4)
									'field' => 17,
								),*/
								array(
									// TEXT8
									'field' => 11,
								)
							),
							'order' => array(
								'sort' => 'ASC',
								'priority' => 10
							),
						),
						2 => array(
							'title' => 'Document type',
							'content' => array(
								array(
									// SUBJECT1
									'field' => 23,
								)
							),
							'order' => array(
								'sort' => 'ASC',
								'priority' => 20
							),
						),
						3 => array(
							'title' => 'Inhoud document',
							'content' => array(
								array(
									// TEXT9
									'field' => 6,
								)
							),
							'order' => array(
								'sort' => 'ASC',
								'priority' => 30
							),
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
						),
						5 => array(
							'title' => 'Zaak',
							'queryOptions' => array(
								array(
									'option' => 'ParentInParent',
									'args' => array(
										// FOLDER
										'itemType' => array(
											2
										)
									)
								),
								/*array(
								 'option' => 'FilterSubItems',
									'args' => array(
										'filters' => array(
											array(
												'value' => 'zake%',
												'operator' => 'LIKE',
												// BOOKNAME
												'field' => 2
											),
											array(
												'value' => '1',
												'operator' => '=',
												// BOL3
												'field' => 16
											)
										),
										'matchAll' => TRUE
									)
								)*/
							),
							'renderOptions' => array(
								/*array(
									'option' => 'Icon',
									'args' => array(
										'name' => 'zaak'
									)
								),*/
								array(
									'option' => 'LinkLevel',
									'args' => array(
										'level' => 4,
										'linkContentItem' => TRUE
									)
								)
							)
						),
						6 => array(
							'title' => 'Reg.datum',
							'content' => array(
								array(
									// DOCUMENT_DATE
									'field' => 21,
									'queryOptions' => array(
										array(
											'option' => 'DateConversion',
											'args' => array(
												'format' => '%d-%m-%Y'
											)
										)
									)
								)
							),
						),
						7 => array(
							'title' => 'Reg.nr.',
							'content' => array(
								array(
									// MARK
									'field' => 4,
								)
							),
						),
					),
					'queryOptions' => array(
						array(
							'option' => 'RestrictByParentId',
							'args' => array(
								'parameter' => '_3',
							)
						),
						array(
							'option' => 'FilterItems',
							'args' => array(
								'filters' => array(
									array(
										'value' => '1',
										'operator' => '=',
										// BOL3
										'field' => 16
									)
								),
							)
						)
					)
				)
			)
		);
		}
	}

	/**
	 * Initializes and validates request parameters shared by all actions
	 *
	 * @return void
	 */
	protected function initializeSharedArguments() {
		if ($this->request->hasArgument('level')) {
			// set current level
			$this->level = (int) $this->request->getArgument('level');
			if ($this->request->hasArgument('_' . $this->level)) {
				$levelParameter = $this->request->getArgument('_' . $this->level);
			}
		}
		// @LOW ___will probably require some validation to see if provided level exists in available configuration
		// @LOW _consider that said check could throw an exception, and that we could then apply an override somewhere that catches it to produce a 404? (or just generate a flash message, which I think we've done in another extbase ext)
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
		$activeConfiguration = $this->pluginConfiguration['level'][$this->level];
		$items = $this->itemRepository->findWithStatement(
			($statement = $this->queryBuilder->buildListQuery(
				$activeConfiguration, $this->pluginConfiguration['import']
			)->createStatement())
		);

		$this->view->assign('configuration', $activeConfiguration);
		$this->view->assign('items', $items);
		// @TODO ___remove
		$this->view->assign('query', $statement->getProcessedQuery());
	}
}
