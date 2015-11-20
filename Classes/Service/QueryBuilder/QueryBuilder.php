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
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent;
/**
 * Query Builder
 *
 * Should be used to initiate the creation of any statement object,
 * that is driven by the extension's main publishing configuration.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class QueryBuilder {
	// @TODO ___perform EXPLAIN on every single query.. maybe log them?
	// @TODO ___add query cache
	// @TODO ___wrong option usage can cause exception to be thrown, which should be displayed nicely on frontend. So where should I catch them?
	// @LOW ___is there any upside to making this a singleton? Consider that many views probably use it twice (to produce a header part)

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory
	 * @inject
	 */
	protected $constraintFactory;

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

	// @TODO ___doc?
	// @TODO ___refactor such huge methods
	// @LOW ___review the ids/keys given to froms/constraints in all methods?
	// @LOW _validate publicationConfig on translation from TS, TCA, or whatever, so we can do away with a bunch of ifs and buts here
	/**
	 * Builds a Query Object for list views
	 *
	 * @param array $configuration
	 * @param array $import
	 * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Query
	 */
	public function buildListQuery(array $configuration, array $import) {
		/** @var $query \Innologi\Decosdata\Service\QueryBuilder\Query\Query */
		$query = $this->objectManager->get(Query::class);

		// init query config
		$queryContent = $query->createContent('itemID');
		$queryContent->getGroupBy()->setPriority(0);
		$queryField = $queryContent->createField('');
		$queryField->getSelect()
			->setField('uid')
			->setTableAlias('it');
		$queryField->createFrom('item', 'tx_decosdata_domain_model_item', 'it');

		// add xml_id-condition if configured
		if (!empty($import)) {
			/*
			 * making this an INNER rather than LEFT JOIN with WHERE, will allow the eq_ref
			 * join-type to use only index (also uses where if NULL-values are possible)
			 */
			$queryField->createFrom('import', 'tx_decosdata_item_import_mm', 'xmm')
				->setJoinType('INNER')
				->setConstraint(
					$this->constraintFactory->createConstraintAnd(array(
						$this->constraintFactory->createConstraintByField('uid_local', 'xmm', '=', 'uid', 'it'),
						$this->constraintFactory->createConstraintByValue('uid_foreign', 'xmm', 'IN', ':import')
					))
				);
			$query->addParameter(':import', $import);
			/*
			 * note that if it.uid is a constant, and more than 1 import is applied in above
			 * restriction, this will result in a range join-type with the amount of xml_id's
			 * as the amount of rows. this inefficiency is partly remedied through automatic
			 * use of join-buffer.
			 */
		}

		// add itemtypes-condition if configured
		if (isset($configuration['itemType'])) {
			$queryField->getWhere()->setConstraint(
				$this->constraintFactory->createConstraintByValue('item_type', 'it', 'IN', ':itemtype')
			);
			$query->addParameter(':itemtype', $configuration['itemType']);
		}

		// expand the query through field configuration
		if (isset($configuration['contentField']) && is_array($configuration['contentField'])) {
			// for each configured content-field..
			foreach ($configuration['contentField'] as $index => $contentConfiguration) {
				$this->addContentField(
					$index,
					$contentConfiguration,
					$query->createContent('content' . $index)
				);
			}
		}

		// apply item-wide query options
		if (isset($configuration['queryOptions'])) {
			// @TODO ___item wide options, e.g. filter/child view????
			$this->optionService->processRowOptions($configuration['queryOptions'], $query);
		}

		// apply pagination settings
		if (isset($configuration['paginate'])) {
			$this->paginateService->configurePagination($configuration['paginate'], $query->createStatement());
			if ($this->paginateService->isReady()) {
				# @LOW _this is a temporary interface until the relevant FIX task in PaginateService is completed
				$query->setLimit(
					$this->paginateService->getLimit(),
					$this->paginateService->getOffset()
				);
			}
		}

		return $query;
	}


	// @TODO ___rename
	public function addContentField($index, array $configuration, QueryContent $queryContent) {
		# @TODO ___remove this and below #s?
		#$names = array(
		#	'returnalias' => 'content',
		#	'returnfield' => 'field_value',
		#	'tablealias' => 'itf'
		#);

		if (isset($configuration['content'])) {
			#$itcol = 'it';
			#$returnfield = 'field_value';
			#$tablealias = 'itf';

			// @TODO ___so how is group concat going to work? also check how options will handle that
			foreach ($configuration['content'] as $subIndex => $contentConfiguration) {
				// add field: these contain metadata strings provided as is by Decos export
				if (isset($contentConfiguration['field'])) {
					$tableAlias = 'itf' . $index . 's' . $subIndex;
					$parameterKey = ':' . $tableAlias . 'field';
					// @TODO ___move to method? wait to see if it really used elsewhere
					$queryField = $queryContent->createField('field' . $subIndex);
					$queryField->getSelect()
						->setField('field_value')
						->setTableAlias($tableAlias);
					$queryField->createFrom(0, 'tx_decosdata_domain_model_itemfield', $tableAlias)
						->setJoinType('LEFT')
						->setConstraint(
							$this->constraintFactory->createConstraintAnd(array(
								$this->constraintFactory->createConstraintByField('item', $tableAlias, '=', 'uid', 'it'),
								$this->constraintFactory->createConstraintByValue('field', $tableAlias, '=', $parameterKey)
							))
						);
					$queryContent->addParameter($parameterKey, $contentConfiguration['field']);
				}

				// add blob: these contain file references, thus will result in file:uid
				if (isset($contentConfiguration['blob'])) {
					// @LOW _if we ever are to support multiple files in a single content, these aliases will conflict
					$aliasId = $index . 's0';
					$fileAlias = 'file' . $aliasId;
					$blobAlias1 = 'itb' . $aliasId;
					$blobAlias2 = 'itb' . $index . 's1';
					$blobTable = 'tx_decosdata_domain_model_itemblob';

					$queryField = $queryContent->createField('blob' . $subIndex);
					// the main join to the blob table
					$queryField->createFrom(0, $blobTable, $blobAlias1)
						->setJoinType('LEFT')
						->setConstraint(
							$this->constraintFactory->createConstraintByField('item', $blobAlias1, '=', 'uid', 'it')
						);
					// a second join is necessary for a maximum-groupwise comparison to always retrieve the latest file
						// maximum-groupwise is performance-wise much preferred over subqueries
					$queryField->createFrom(1, $blobTable, $blobAlias2)
						->setJoinType('LEFT')
						->setConstraint(
							$this->constraintFactory->createConstraintAnd(array(
								$this->constraintFactory->createConstraintByField('item', $blobAlias2, '=', 'uid', 'it'),
								$this->constraintFactory->createConstraintByField('sequence', $blobAlias2, '<', 'sequence', $blobAlias1)
							))
						);
					$queryField->getWhere()->setConstraint(
						$this->constraintFactory->createConstraintByValue('uid', $blobAlias2, 'IS', 'NULL')
					);

					// retrieve associated file uid from file reference table
					$parameterKey = ':' . $fileAlias . 'table';
					$queryField->getSelect()
						->setField('uid_local')
						->setTableAlias($fileAlias)
							// @TODO ___what happens here if we don't have a file uid? do we get a 'file:' ?
						->addWrap('file', 'CONCAT(\'file:\',|)');
					$queryField->createFrom(2, 'sys_file_reference', $fileAlias)
						->setJoinType('LEFT')
						->setConstraint(
							$this->constraintFactory->createConstraintAnd(array(
								$this->constraintFactory->createConstraintByField('uid_foreign', $fileAlias, '=', 'uid', $blobAlias1),
								$this->constraintFactory->createConstraintByValue('tablenames', $fileAlias, '=', $parameterKey)
							))
						);
					$queryContent->addParameter($parameterKey, $blobTable);
				}

				/*if (isset($contentConfig['order'])) {
					// @LOW add ordering by field?
				}*/

				// apply field-wide query options
				if (isset($contentConfiguration['queryOptions'])) {
					$this->optionService->processFieldOptions($contentConfiguration['queryOptions'], $queryField);
				}
			}

		// if $valueArray is empty, provide NULL so that at least the alias can exist for compatibility
		} else {
			// @TODO ___keep or throw out? do we still support this now that we support options outside of columns? Maybe options that provide a value from somewhere else?
			#$queryConfiguration[]['SELECT'] = array('field' => 'NULL');
		}

		// set content-wide ordering
		if (isset($configuration['order'])) {
			$queryContent->getOrderBy()
				->setPriority($configuration['order']['priority'])
				->setSortOrder($configuration['order']['sort']);
		}

		// apply content-wide query options
		if (isset($configuration['queryOptions'])) {
			$this->optionService->processColumnOptions($configuration['queryOptions'], $queryContent);
		}
	}

}
