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
	 * @var array
	 */
	protected $piConf = array(
		'import' => array(
			2
		),
		'level' => array(
			1 => array(
				'paginate' => array(
					'pageLimit' => 30,
					'perPageLimit' => '50'
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

	/**
	 * List items per publication configuration.
	 *
	 * @return void
	 */
	public function listAction() {
		$level = 1;
		// @TODO ___do we force a single query method, or do we allow a choice set by configuration?
		// @FIX ________add pagebrowser config.. via widget? or better via querybuilder?
		// @TODO _rename?
		$items = $this->itemRepository->findWithStatement(
			($statement = $this->queryBuilder->buildListQuery(
				$this->piConf['level'][$level], $this->piConf['import']
			)->createStatement())
		);

		$this->view->assign('configuration', $this->piConf['level'][$level]);
		$this->view->assign('contentFieldCount', count($this->piConf['level'][$level]['contentField']));
		$this->view->assign('items', $items);
		// @TODO ___remove
		$this->view->assign('query', $statement->getQuery());
	}
}
