<?php

namespace Innologi\Decosdata\Service\QueryBuilder;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015-2019 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
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
use Innologi\Decosdata\Service\Option\QueryOptionService;
use Innologi\Decosdata\Service\PaginateService;
use Innologi\Decosdata\Service\QueryBuilder\Query\Constraint\ConstraintFactory;
use Innologi\Decosdata\Service\QueryBuilder\Query\Query;
use Innologi\Decosdata\Service\QueryBuilder\Query\QueryContent;
use Innologi\Decosdata\Service\SearchService;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

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
class QueryBuilder
{
    // @TODO ___perform EXPLAIN on every single query.. maybe log them?
    // @TODO ___add query cache
    // @TODO ___wrong option usage can cause exception to be thrown, which should be displayed nicely on frontend. So where should I catch them?
    // @LOW ___is there any upside to making this a singleton? Consider that many views probably use it twice (to produce a header part)

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ConstraintFactory
     */
    protected $constraintFactory;

    /**
     * @var QueryOptionService
     */
    protected $optionService;

    /**
     * @var PaginateService
     */
    protected $paginateService;

    /**
     * @var SearchService
     */
    protected $searchService;

    /**
     * @var integer
     */
    protected $groupByContentPriority;

    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function injectConstraintFactory(ConstraintFactory $constraintFactory)
    {
        $this->constraintFactory = $constraintFactory;
    }

    public function injectOptionService(QueryOptionService $optionService)
    {
        $this->optionService = $optionService;
    }

    public function injectPaginateService(PaginateService $paginateService)
    {
        $this->paginateService = $paginateService;
    }

    public function injectSearchService(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    // @TODO ___doc?
    // @TODO ___refactor such huge methods
    // @LOW ___review the ids/keys given to froms/constraints in all methods?
    // @LOW _validate publicationConfig on translation from TS, TCA, or whatever, so we can do away with a bunch of ifs and buts here
    /**
     * Builds a Query Object for list views
     *
     * @return \Innologi\Decosdata\Service\QueryBuilder\Query\Query
     */
    public function buildListQuery(array &$configuration, array $import)
    {
        /** @var \Innologi\Decosdata\Service\QueryBuilder\Query\Query $query */
        $query = $this->objectManager->get(Query::class);

        // init query config
        $queryContent = $query->getContent('id');
        $queryField = $queryContent->getField('');
        $queryField->getSelect()
            ->setField('uid')
            ->setTableAlias('it');
        $queryField->getFrom('item', [
            'it' => 'tx_decosdata_domain_model_item',
        ]);

        $groupByContent = isset($configuration['groupByContent']) && (bool) $configuration['groupByContent'];
        $this->groupByContentPriority = 0;
        if (!$groupByContent) {
            // if not groupByContent, group by id column first and foremost
            $queryContent->getGroupBy()->setPriority(0);
        }

        // add xml_id-condition if configured
        if (!empty($import)) {
            // @TODO check on larger datasets, if adding an x table join speeds up the query
            // initial testing seems to indicate fewer rows through better index-use of x's PRIMARY
            /*
             * making this an INNER rather than LEFT JOIN with WHERE, will allow the eq_ref
             * join-type to use only index (also uses where if NULL-values are possible)
             */
            $queryField->getFrom('import', [
                'xmm' => 'tx_decosdata_item_import_mm',
            ])
                ->setJoinType('INNER')
                ->setConstraint(
                    $this->constraintFactory->createConstraintAnd([
                        $this->constraintFactory->createConstraintByField('uid_local', 'xmm', '=', 'uid', 'it'),
                        $this->constraintFactory->createConstraintByValue('uid_foreign', 'xmm', 'IN', ':import'),
                    ]),
                );
            $query->addParameter(':import', $import);
            /*
             * note that if it.uid is a constant, and more than 1 import is applied in above
             * restriction, this will result in a range join-type with the amount of xml_id's
             * as the amount of rows. this inefficiency is partly remedied through automatic
             * use of join-buffer.
             */
            // @TODO check if the above text is still true and if it needs further optimization, when testing with large datasets
        }

        // add itemtypes-condition if configured
        if (isset($configuration['itemType'])) {
            $queryField->getFrom('itemType', [
                'itt' => 'tx_decosdata_domain_model_itemtype',
            ])
                ->setJoinType('INNER')
                ->setConstraint(
                    $this->constraintFactory->createConstraintAnd([
                        $this->constraintFactory->createConstraintByField('uid', 'itt', '=', 'item_type', 'it'),
                        $this->constraintFactory->createConstraintByValue('item_type', 'itt', 'IN', ':itemtype'),
                    ]),
                );
            $query->addParameter(':itemtype', $configuration['itemType']);
        }

        // apply search configuration
        if (isset($configuration['searchable']) && is_array($configuration['searchable']) && $this->searchService->isActive()) {
            $configuration['queryOptions'] = $this->searchService->configureSearch($configuration['searchable'], $configuration['queryOptions']);
        }

        // apply item-wide query options
        if (isset($configuration['queryOptions']) && is_array($configuration['queryOptions'])) {
            // @TODO ___item wide options, e.g. filter/child view????
            $this->optionService->processRowOptions($configuration['queryOptions'], $query);
        }

        // expand the query through field configuration
        if (isset($configuration['contentField']) && is_array($configuration['contentField'])) {
            // for each configured content-field..
            foreach ($configuration['contentField'] as $index => $contentConfiguration) {
                $this->addContentField(
                    $index,
                    $contentConfiguration,
                    $query->getContent('content' . $index),
                    $groupByContent,
                );
            }
        }

        // set ordering through any field outside of normal content/fields
        if (isset($configuration['order']) && isset($configuration['order']['field'])) {
            // @TODO check if the field exists
            // @TODO align this with how AddFields and every else works
            $query->getContent($configuration['order']['field'])
                ->getOrderBy()
                ->setPriority($configuration['order']['priority'])
                ->setSortOrder($configuration['order']['sort'])
                ->setForceNumeric(isset($configuration['order']['forceNumeric']) && (bool) $configuration['order']['forceNumeric']);
        }

        // apply pagination settings
        if (isset($configuration['paginate']) && is_array($configuration['paginate'])) {
            $this->paginateService->configurePagination($configuration['paginate'], $query);
        }

        return $query;
    }


    // @TODO ___rename
    public function addContentField($index, array $configuration, QueryContent $queryContent, $groupByContent = false)
    {
        # @TODO ___remove this and below #s?
        #$names = [
        #	'returnalias' => 'content',
        #	'returnfield' => 'field_value',
        #	'tablealias' => 'itf'
        #];

        // if group by content, enforce groupby
        if ($groupByContent) {
            $queryContent->getGroupBy()->setPriority(
                $this->groupByContentPriority++,
            );
        }

        if (isset($configuration['content'])) {
            #$itcol = 'it';
            #$returnfield = 'field_value';
            #$tablealias = 'itf';
            $idAliases = [];

            // @TODO ___so how is group concat going to work? also check how options will handle that
            foreach ($configuration['content'] as $subIndex => $contentConfiguration) {
                $queryField = null;

                // add field: these contain metadata strings provided as is by Decos export
                if (isset($contentConfiguration['field'])) {
                    // @TODO ___field, blob and order keys could probably be converted to more configurable queryOptions, to make it all more flexible.
                    // We could re-introduce document_date for blobs then and make that configurable too, as well as which of multiple blobs to get.
                    // We could then keep field, blob and order keys as shortcuts for TCA/Typoscript configuration, which are transformed by a
                    // future service which translates these to standard options. Do that for all the stuff that is common, like a download link
                    // and restrictByItem/ParentItem, et voila, you combine the ease of use of said shortcuts with the flexibility of queryoption-equivalents.
                    // @LOW ___Now that I think about it, the same goes for itemtype and import.. except I don't think they have anything to gain in flexibility.
                    $tableAlias1 = 'itf' . $index . 's' . $subIndex;
                    $tableAlias2 = 'f' . $index . 's' . $subIndex;
                    $parameterKey = ':' . $tableAlias1 . 'field';
                    $idAliases[] = $tableAlias1;
                    // @TODO ___move to method? wait to see if it really used elsewhere
                    $queryField = $queryContent->getField('field' . $subIndex);
                    $queryField->getSelect()
                        ->setField('field_value')
                        ->setTableAlias($tableAlias1);

                    $queryField->getFrom(0, [
                        $tableAlias1 => 'tx_decosdata_domain_model_itemfield',
                        $tableAlias2 => 'tx_decosdata_domain_model_field',
                    ])->setJoinType('LEFT')
                        ->setConstraint(
                            $this->constraintFactory->createConstraintAnd([
                                $this->constraintFactory->createConstraintByField('item', $tableAlias1, '=', 'uid', 'it'),
                                $this->constraintFactory->createConstraintByField('field', $tableAlias1, '=', 'uid', $tableAlias2),
                                $this->constraintFactory->createConstraintByValue('field_name', $tableAlias2, '=', $parameterKey),
                            ]),
                        );
                    $queryContent->addParameter($parameterKey, $contentConfiguration['field']);
                }
                // @LOW ___can we have both a field and blob in the same content? no right? because this should be an if/else then
                // @TODO ___why do it this way? why not use a linkvalue equivalent? then we can combine with any value. probably a lot simpler
                // add blob: these contain file references, thus will result in file:uid
                if (isset($contentConfiguration['blob'])) {
                    // @LOW _if we ever are to support multiple files in a single content, these aliases will conflict
                    $aliasId = $index . 's0';
                    $fileAlias = 'file' . $aliasId;
                    $blobAlias1 = 'itb' . $aliasId;
                    $blobAlias2 = 'itb' . $index . 's1';
                    $blobTable = 'tx_decosdata_domain_model_itemblob';
                    $idAliases[] = $blobAlias1;

                    $queryField = $queryContent->getField('blob' . $subIndex);

                    // note that these joins should not be combined into a single JOIN

                    // the main join to the blob table
                    $queryField->getFrom('blob1', [
                        $blobAlias1 => $blobTable,
                    ])
                        ->setJoinType('LEFT')
                        ->setConstraint(
                            $this->constraintFactory->createConstraintByField('item', $blobAlias1, '=', 'uid', 'it'),
                        );
                    // a second join is necessary for a maximum-groupwise comparison to always retrieve the latest file
                    // maximum-groupwise is performance-wise much preferred over subqueries
                    $queryField->getFrom('blob2', [
                        $blobAlias2 => $blobTable,
                    ])
                        ->setJoinType('LEFT')
                        ->setConstraint(
                            $this->constraintFactory->createConstraintAnd([
                                $this->constraintFactory->createConstraintByField('item', $blobAlias2, '=', 'uid', 'it'),
                                // @TODO ___note that this does not produce the same result as those that still use document_date. Need to see if Heemskerk is affected with newer tests
                                $this->constraintFactory->createConstraintByField('sequence', $blobAlias2, '>', 'sequence', $blobAlias1),
                            ]),
                        );
                    $queryField->getWhere()->setConstraint(
                        $this->constraintFactory->createConstraintByValue('uid', $blobAlias2, 'IS', 'NULL'),
                    );

                    // after maximum-groupwise: retrieve associated file uid from file reference table
                    $parameterKey = ':' . $fileAlias . 'table';
                    $queryField->getSelect()
                        ->setField('uid_local')
                        ->setTableAlias($fileAlias)
                            // @TODO ___what happens here if we don't have a file uid? do we get a 'file:' ?
                        ->addWrap('file', 'CONCAT(\'file:\',|)');
                    $queryField->getFrom('fileref', [
                        $fileAlias => 'sys_file_reference',
                    ])
                        ->setJoinType('LEFT')
                        ->setConstraint(
                            $this->constraintFactory->createConstraintAnd([
                                $this->constraintFactory->createConstraintByField('uid_foreign', $fileAlias, '=', 'uid', $blobAlias1),
                                $this->constraintFactory->createConstraintByValue('tablenames', $fileAlias, '=', $parameterKey),
                            ]),
                        );
                    $queryContent->addParameter($parameterKey, $blobTable);
                }

                // apply sorting on field
                if (isset($contentConfiguration['order']) && $queryField !== null) {
                    $select = $queryField->getSelect();
                    $queryField->getOrderBy()
                        ->setTableAlias($select->getTableAlias())
                        ->setField($select->getField())
                        ->setPriority($contentConfiguration['order']['priority'])
                        ->setSortOrder($contentConfiguration['order']['sort'])
                        ->setForceNumeric(isset($contentConfiguration['order']['forceNumeric']) && (bool) $contentConfiguration['order']['forceNumeric']);
                }

                // apply field-wide query options
                if (isset($contentConfiguration['queryOptions'])) {
                    // @TODO ___throw catchable exception if $queryField is NULL? (which is the case if no field/blob configuration is present)
                    $this->optionService->processFieldOptions($contentConfiguration['queryOptions'], $queryField, $subIndex);
                }
            }

            // add content itemfield id's
            if (!empty($idAliases)) {
                $idContent = $queryContent->getParent()->getContent('id' . $index)->setFieldSeparator('-');
                foreach ($idAliases as $i => $idAlias) {
                    $idContent->getField($i)->getSelect()->setField('uid')->setTableAlias($idAlias);
                }
            }

            // if $valueArray is empty, provide NULL so that at least the alias can exist for compatibility
        }
        // @TODO ___keep or throw out? do we still support this now that we support options outside of columns? Maybe options that provide a value from somewhere else?
        #$queryConfiguration[]['SELECT'] = ['field' => 'NULL'];


        // set content-wide ordering
        if (isset($configuration['order'])) {
            $queryContent->getOrderBy()
                ->setPriority($configuration['order']['priority'])
                ->setSortOrder($configuration['order']['sort'])
                ->setForceNumeric(isset($configuration['order']['forceNumeric']) && (bool) $configuration['order']['forceNumeric']);
        }

        // apply content-wide query options
        if (isset($configuration['queryOptions'])) {
            $this->optionService->processColumnOptions($configuration['queryOptions'], $queryContent, $index);
        }
    }
}
