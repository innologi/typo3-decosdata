<?php
namespace Innologi\Decosdata\Service\QueryBuilder;
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
 * Query Builder
 *
 * Should be used to initiate the creation of any query object,
 * that is driven by the extension's main publishing configuration.
 *
 * Not designed to be a singleton!
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryBuilder {
	// @TODO ___perform EXPLAIN on every single query.. maybe log them?
	// @TODO ___add query cache
	// @TODO ___wrong option usage can cause exception to be thrown, which should be displayed nicely on frontend. So where should I catch them?
	// @LOW ___singleton? why not?

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\QueryFactory
	 * @inject
	 */
	protected $queryFactory;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\QueryConfigurator
	 * @inject
	 */
	protected $queryConfigurator;

	/**
	 * @var \Innologi\Decosdata\Service\Option\QueryOptionService
	 * @inject
	 */
	protected $optionService;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\PaginateService
	 * @inject
	 */
	protected $paginateService;

	/**
	 * @var array
	 */
	protected $queryParts = array();

	/**
	 * @var array
	 */
	protected $parameterParts = array();

	/**
	 * Class constructor
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder
	 */
	public function __construct() {
		return $this->reset();
	}

	/**
	 * Resets the QueryBuilder to start with fresh query and parameter parts
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder
	 */
	public function reset() {
		$this->queryParts = array(
			'SELECT' => '',
			'FROM' => '',
			'WHERE' => '',
			'GROUPBY' => '',
			'ORDERBY' => '',
			'LIMIT' => ''
		);
		$this->parameterParts = array(
			'SELECT' => array(),
			'FROM' => array(),
			'WHERE' => array(),
			'GROUPBY' => array(),
			'LIMIT' => array()
		);
		return $this;
	}

	/**
	 * Get query parts
	 *
	 * @return array
	 */
	public function getQueryParts() {
		return $this->queryParts;
	}

	/**
	 * Sets query parts
	 *
	 * @param array $queryParts
	 * @return \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder
	 */
	public function setQueryParts(array $queryParts) {
		$this->queryParts = $queryParts;
		return $this;
	}

	/**
	 * Gets parameter parts
	 *
	 * @return array
	 */
	public function getParameterParts() {
		return $this->parameterParts;
	}

	/**
	 * Sets parameter parts
	 *
	 * @param array $parameterParts
	 * @return \Innologi\Decosdata\Service\QueryBuilder\QueryBuilder
	 */
	public function setParameterParts(array $parameterParts) {
		$this->parameterParts = $parameterParts;
		return $this;
	}

	/**
	 * Build a Query object from already existing query- and parameter- parts
	 *
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query
	 */
	public function build() {
		return $this->queryFactory->create($this->queryParts, $this->parameterParts);
	}

	// @TODO ___doc?
	// @TODO ___refactor such huge methods
	/**
	 * Builds a Query Object for list views
	 *
	 * @param array $publicationConfig
	 * @param array $import
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query
	 */
	public function buildListQuery(array $publicationConfig, array $import) {
		// init query config
		$queryConfiguration = array(
			'SELECT' => array(
				'field' => 'it.uid',
			),
			'FROM' => array(
				$this->queryConfigurator->provideFrom('tx_decosdata_domain_model_item', 'it')
			),
			'GROUPBY' => array(
				'priority' => 0
			)
		);

		// add xml_id-condition if configured
		if (!empty($import)) {
			/*
			 * making this an INNER rather than LEFT JOIN with WHERE, will allow the eq_ref
			 * join-type to use only index (also uses where if NULL-values are possible)
			 */
			$queryConfiguration['FROM'][] = $this->queryConfigurator->provideFrom(
				'tx_decosdata_item_import_mm', 'xmm', 'INNER', array('uid_local/=/it/uid','uid_foreign/IN/?')
			);
			$queryConfiguration['PARAMETER'] = array(
				'FROM' => array(
					$import
				)
			);
			/*
			 * note that if it.uid is a constant, and more than 1 import is applied in above
			 * restriction, this will result in a range join-type with the amount of xml_id's
			 * as the amount of rows. this inefficiency is partly remedied through automatic
			 * use of join-buffer.
			 */
		}

		// add itemtypes-condition if configured
		if (isset($publicationConfig['itemType'])) {
			$queryConfiguration['WHERE'] = array(
				$this->queryConfigurator->provideWhereConditionUnsafe('it.item_type', 'IN', '?')
			);
			$queryConfiguration['PARAMETER']['WHERE'] = array($publicationConfig['itemType']);
		}

		// put everything under the initial ID (not a contentfield)
		$queryConfiguration = array(
			'itemID' => array($queryConfiguration)
		);

		// expand the query through field configuration
		if (isset($publicationConfig['contentField']) && is_array($publicationConfig['contentField'])) {
			// for each configured content-field..
			foreach ($publicationConfig['contentField'] as $index => $contentFieldConfiguration) {
				$queryConfiguration['content' . $index] = $this->addContentField($index, $contentFieldConfiguration);
			}
		}

		// apply item-wide query options
		if (isset($publicationConfig['queryOptions'])) {
			// @TODO ___item wide options, e.g. filter/child view????
			$this->optionService->processRowOptions($publicationConfig['queryOptions'], $queryConfiguration, $this);
		}

		$this->queryConfigurator->transformConfiguration($queryConfiguration, $this->queryParts, $this->parameterParts);
		if ($publicationConfig['paginate']) {
			$this->paginateService->paginateQuery($publicationConfig['paginate'], $this->queryParts, $this->parameterParts);
		}


		return $this->build();
	}


	// @TODO ___rename
	public function addContentField($index, array $configuration) {
		$names = array(
			# @TODO ___remove this and below #s?
			#'returnalias' => 'content',
			'returnfield' => 'field_value',
			'tablealias' => 'itf'
		);

		if (isset($configuration['content'])) {
			$itcol = 'it';
			$queryConfiguration = array();

			// @TODO ___so how is group concat going to work? also check how options will handle that

			foreach ($configuration['content'] as $subIndex => $contentConfig) {
				$queryConfiguration[$subIndex] = array();

				// add field: these contain metadata strings provided as is by Decos export
				if (isset($contentConfig['field'])) {
					$aliasId = '' . $index . 's' . $subIndex;
					// @TODO ___move to method? wait to see if it really used elsewhere
					$queryConfiguration[$subIndex]['SELECT'] = array(
						'field' => $names['tablealias'] . $aliasId . '.' . $names['returnfield'],
						#'alias' => $names['returnalias'] . $aliasId
					);
					$queryConfiguration[$subIndex]['FROM'][] = $this->queryConfigurator->provideFrom(
						'tx_decosdata_domain_model_itemfield', $names['tablealias'].$aliasId, 'LEFT', array('item/=/'.$itcol.'/uid','field/=/?')
					);
					$queryConfiguration[$subIndex]['PARAMETER']['FROM'][] = $contentConfig['field'];
				}

				// add blob: these contain file references, thus will result in file:uid
				if (isset($contentConfig['blob'])) {
					$aliasId = '' . $index . 's0';
					$aliasId2 = '' . $index . 's1';
					$queryConfiguration[$subIndex]['SELECT'] = array(
						'field' => 'file' . $aliasId. '.uid_local',
						// @TODO ___what happens here if we don't have a file uid? do we get a 'file:' ?
						'wrap' => array('CONCAT(\'file:\',|)')
					);
					$queryConfiguration[$subIndex]['FROM'][] = $this->queryConfigurator->provideFrom(
						'tx_decosdata_domain_model_itemblob', 'itb'.$aliasId, 'LEFT', array('item/=/'.$itcol.'/uid')
					);

					// a second join is necessary for a maximum-groupwise comparison to always retrieve the latest file
						// maximum-groupwise is performance-wise much preferred to subqueries
					$queryConfiguration[$subIndex]['FROM'][] = $this->queryConfigurator->provideFrom(
						'tx_decosdata_domain_model_itemblob', 'itb'.$aliasId2, 'LEFT', array('item/=/'.$itcol.'/uid','sequence/</itb'.$aliasId.'/sequence')
					);
					$queryConfiguration[$subIndex]['WHERE'][] = $this->queryConfigurator->provideWhereConditionUnsafe('itb'.$aliasId2.'.uid', 'IS', 'NULL');

					// retrieve associated file uid from file reference table
					$queryConfiguration[$subIndex]['FROM'][] = $this->queryConfigurator->provideFrom(
						'sys_file_reference', 'file'.$aliasId, 'LEFT', array('uid_foreign/=/itb'.$aliasId.'/uid','tablenames/=/?')
					);
					$queryConfiguration[$subIndex]['PARAMETER']['FROM'][] = 'tx_decosdata_domain_model_itemblob';

				}

				/*if (isset($contentConfig['order'][0])) {
					// @LOW add ordering by field?
				}*/

				// apply field-wide query options
				if (isset($contentConfig['queryOptions'])) {
					$this->optionService->processFieldOptions($contentConfig['queryOptions'], $queryConfiguration[$subIndex], $this);
				}
			}

		// if $valueArray is empty, provide NULL so that at least the alias can exist for compatibility
		} else {
			// @TODO ___keep or throw out? do we still support this now that we support options outside of columns? Maybe options that provide a value from somewhere else?
			$queryConfiguration[]['SELECT'] = array('field' => 'NULL');
		}

		// set content-wide ordering
		if (isset($configuration['order'])) {
			$queryConfiguration[]['ORDERBY'] = $configuration['order'];
		}

		// apply content-wide query options
		if (isset($configuration['queryOptions'])) {
			$this->optionService->processColumnOptions($configuration['queryOptions'], $queryConfiguration, $this);
		}

		return $queryConfiguration;
	}

}
