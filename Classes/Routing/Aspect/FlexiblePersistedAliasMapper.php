<?php

namespace Innologi\Decosdata\Routing\Aspect;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2019-2022 Frenck Lutke <typo3@innologi.nl>, www.innologi.nl
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Flexible Persisted Alias Mapper
 *
 * Allows for more configurability (e.g. joins and constraints) compared to the
 * original PersistedAliasMapper, as well as simple route transformations, such as:
 * - lowercase
 * - date-formatting
 * - pattern-based character replacement
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class FlexiblePersistedAliasMapper extends PersistedAliasMapper
{
    /**
     * @var string
     */
    protected $tableAlias;

    /**
     * @var string
     */
    protected $tableAliasPrefix = '';

    /**
     * @var array
     */
    protected $tableJoins = [];

    /**
     * @var array
     */
    protected $constraints = [];

    /**
     * @var string
     */
    protected $idFieldName;

    /**
     * @var array
     */
    protected $diacritics = [
        'À',
        'Á',
        'Â',
        'Ã',
        'Ä',
        'Å',
        'Æ',
        'Ç',
        'È',
        'É',
        'Ê',
        'Ë',
        'Ì',
        'Í',
        'Î',
        'Ï',
        'Ð',
        'Ñ',
        'Ò',
        'Ó',
        'Ô',
        'Õ',
        'Ö',
        'Ø',
        'Ù',
        'Ú',
        'Û',
        'Ü',
        'Ý',
        'ß',
        'à',
        'á',
        'â',
        'ã',
        'ä',
        'å',
        'æ',
        'ç',
        'è',
        'é',
        'ê',
        'ë',
        'ì',
        'í',
        'î',
        'ï',
        'ñ',
        'ò',
        'ó',
        'ô',
        'õ',
        'ö',
        'ø',
        'ù',
        'ú',
        'û',
        'ü',
        'ý',
        'ÿ',
        'Ā',
        'ā',
        'Ă',
        'ă',
        'Ą',
        'ą',
        'Ć',
        'ć',
        'Ĉ',
        'ĉ',
        'Ċ',
        'ċ',
        'Č',
        'č',
        'Ď',
        'ď',
        'Đ',
        'đ',
        'Ē',
        'ē',
        'Ĕ',
        'ĕ',
        'Ė',
        'ė',
        'Ę',
        'ę',
        'Ě',
        'ě',
        'Ĝ',
        'ĝ',
        'Ğ',
        'ğ',
        'Ġ',
        'ġ',
        'Ģ',
        'ģ',
        'Ĥ',
        'ĥ',
        'Ħ',
        'ħ',
        'Ĩ',
        'ĩ',
        'Ī',
        'ī',
        'Ĭ',
        'ĭ',
        'Į',
        'į',
        'İ',
        'ı',
        'Ĳ',
        'ĳ',
        'Ĵ',
        'ĵ',
        'Ķ',
        'ķ',
        'Ĺ',
        'ĺ',
        'Ļ',
        'ļ',
        'Ľ',
        'ľ',
        'Ŀ',
        'ŀ',
        'Ł',
        'ł',
        'Ń',
        'ń',
        'Ņ',
        'ņ',
        'Ň',
        'ň',
        'ŉ',
        'Ō',
        'ō',
        'Ŏ',
        'ŏ',
        'Ő',
        'ő',
        'Œ',
        'œ',
        'Ŕ',
        'ŕ',
        'Ŗ',
        'ŗ',
        'Ř',
        'ř',
        'Ś',
        'ś',
        'Ŝ',
        'ŝ',
        'Ş',
        'ş',
        'Š',
        'š',
        'Ţ',
        'ţ',
        'Ť',
        'ť',
        'Ŧ',
        'ŧ',
        'Ũ',
        'ũ',
        'Ū',
        'ū',
        'Ŭ',
        'ŭ',
        'Ů',
        'ů',
        'Ű',
        'ű',
        'Ų',
        'ų',
        'Ŵ',
        'ŵ',
        'Ŷ',
        'ŷ',
        'Ÿ',
        'Ź',
        'ź',
        'Ż',
        'ż',
        'Ž',
        'ž',
        'ſ',
        'ƒ',
        'Ơ',
        'ơ',
        'Ư',
        'ư',
        'Ǎ',
        'ǎ',
        'Ǐ',
        'ǐ',
        'Ǒ',
        'ǒ',
        'Ǔ',
        'ǔ',
        'Ǖ',
        'ǖ',
        'Ǘ',
        'ǘ',
        'Ǚ',
        'ǚ',
        'Ǜ',
        'ǜ',
        'Ǻ',
        'ǻ',
        'Ǽ',
        'ǽ',
        'Ǿ',
        'ǿ',
    ];

    /**
     * @var array
     */
    protected $diacriticReplacements = [
        'A',
        'A',
        'A',
        'A',
        'A',
        'A',
        'AE',
        'C',
        'E',
        'E',
        'E',
        'E',
        'I',
        'I',
        'I',
        'I',
        'D',
        'N',
        'O',
        'O',
        'O',
        'O',
        'O',
        'O',
        'U',
        'U',
        'U',
        'U',
        'Y',
        's',
        'a',
        'a',
        'a',
        'a',
        'a',
        'a',
        'ae',
        'c',
        'e',
        'e',
        'e',
        'e',
        'i',
        'i',
        'i',
        'i',
        'n',
        'o',
        'o',
        'o',
        'o',
        'o',
        'o',
        'u',
        'u',
        'u',
        'u',
        'y',
        'y',
        'A',
        'a',
        'A',
        'a',
        'A',
        'a',
        'C',
        'c',
        'C',
        'c',
        'C',
        'c',
        'C',
        'c',
        'D',
        'd',
        'D',
        'd',
        'E',
        'e',
        'E',
        'e',
        'E',
        'e',
        'E',
        'e',
        'E',
        'e',
        'G',
        'g',
        'G',
        'g',
        'G',
        'g',
        'G',
        'g',
        'H',
        'h',
        'H',
        'h',
        'I',
        'i',
        'I',
        'i',
        'I',
        'i',
        'I',
        'i',
        'I',
        'i',
        'IJ',
        'ij',
        'J',
        'j',
        'K',
        'k',
        'L',
        'l',
        'L',
        'l',
        'L',
        'l',
        'L',
        'l',
        'l',
        'l',
        'N',
        'n',
        'N',
        'n',
        'N',
        'n',
        'n',
        'O',
        'o',
        'O',
        'o',
        'O',
        'o',
        'OE',
        'oe',
        'R',
        'r',
        'R',
        'r',
        'R',
        'r',
        'S',
        's',
        'S',
        's',
        'S',
        's',
        'S',
        's',
        'T',
        't',
        'T',
        't',
        'T',
        't',
        'U',
        'u',
        'U',
        'u',
        'U',
        'u',
        'U',
        'u',
        'U',
        'u',
        'U',
        'u',
        'W',
        'w',
        'Y',
        'y',
        'Y',
        'Z',
        'z',
        'Z',
        'z',
        'Z',
        'z',
        's',
        'f',
        'O',
        'o',
        'U',
        'u',
        'A',
        'a',
        'I',
        'i',
        'O',
        'o',
        'U',
        'u',
        'U',
        'u',
        'U',
        'u',
        'U',
        'u',
        'U',
        'u',
        'A',
        'a',
        'AE',
        'ae',
        'O',
        'o',
    ];

    /**
     * @var array
     */
    protected $transformRoute = [
        'lowerCase' => false,
        'replaceDiacritics' => false,
        'replacementChar' => null,
        'replacementMatch' => null,
        'dateTimeFormat' => null,
        'maxLength' => null,
    ];

    /**
     * Conceptually, caching of RouteEnhancers should be delegated to
     * the core's routing component.
     * Until then, we will cache the use
     * of this aspect ourselves.
     *
     * Can be disabled for debugging purposes. Note that if a route has
     * a transformRoute config, it will not be able to resolve most
     * URLs without cache.
     *
     * @var boolean
     */
    protected $cacheResult = true;

    /**
     * @todo allow this table to be cleared by persistent db table flush tool
     * @var string
     */
    protected $cacheTable = 'tx_decosdata_routing_slug';

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    public function __construct(array $settings)
    {
        $tableAlias = $settings['tableAlias'] ?? null;
        $tableJoins = $settings['tableJoins'] ?? [];
        $constraints = $settings['constraints'] ?? [];
        $idFieldName = $settings['idFieldName'] ?? 'uid';
        $transformRoute = $settings['transformRoute'] ?? [];
        $cacheResult = $settings['cacheResult'] ?? true;

        if ($tableAlias !== null && !\is_string($tableAlias)) {
            throw new \InvalidArgumentException('tableAlias must be string', 1564488228);
        }
        if (!\is_array($tableJoins)) {
            throw new \InvalidArgumentException('tableJoins must be array', 1564488674);
        }
        if (!\is_array($constraints)) {
            throw new \InvalidArgumentException('constraints must be array', 1564497390);
        }
        if (!\is_string($idFieldName)) {
            throw new \InvalidArgumentException('idFieldName must be string', 1564474975);
        }
        if (!\is_array($transformRoute)) {
            throw new \InvalidArgumentException('transformRoute must be array', 1564476648);
        } elseif (!empty($transformRoute)) {
            $transformRoute = \array_merge($this->transformRoute, $transformRoute);
        }
        if (!\is_bool($cacheResult)) {
            throw new \InvalidArgumentException('cacheResult must be boolean', 1564561108);
        }

        $this->tableAlias = $tableAlias;
        $this->tableAliasPrefix = isset($tableAlias[0]) ? $tableAlias . '.' : '';
        $this->tableJoins = $tableJoins;
        $this->constraints = $constraints;
        $this->idFieldName = $idFieldName;
        $this->transformRoute = $transformRoute;
        $this->cacheResult = $cacheResult;

        parent::__construct($settings);
    }

    public function generate(string $value): ?string
    {
        return $this->cacheResult ? $this->getCachedSlug($value) : $this->generateRouteValue($value);
    }

    public function resolve(string $value): ?string
    {
        return $this->cacheResult ? $this->getCachedRouteVar($value) : $this->resolveRouteValue($value);
    }

    protected function generateRouteValue(string $value): ?string
    {
        $result = $this->findByIdentifiers([
            $this->tableAliasPrefix . $this->idFieldName => $value,
        ]);
        if (!isset($result[$this->routeFieldName][0])) {
            return null;
        }
        $routeValue = $this->transformRouteValue($this->purgeRouteValuePrefix($result[$this->routeFieldName]));
        if ($this->cacheResult) {
            $this->storeCacheResult($value, $this->ensureUniqueValue($routeValue));
        }
        return $routeValue;
    }

    protected function resolveRouteValue(string $value): ?string
    {
        $value = $this->routeValuePrefix . $this->purgeRouteValuePrefix($value);
        $result = $this->findByIdentifiers([
            $this->tableAliasPrefix . $this->routeFieldName => $value,
        ]);
        if (isset($result[$this->idFieldName])) {
            return (string) $result[$this->idFieldName];
        }
        return null;
    }

    protected function getCachedSlug(string $value): ?string
    {
        $cache = $this->getCachedResult([
            'routevar' => $value,
        ]);
        return isset($cache['slug']) ? (string) $cache['slug'] : $this->generateRouteValue($value);
    }

    protected function getCachedRouteVar(string $value): ?string
    {
        $cache = $this->getCachedResult([
            'slug' => $value,
        ]);
        return isset($cache['routevar']) ? (string) $cache['routevar'] : $this->resolveRouteValue($value);
    }

    /**
     * @return mixed
     */
    protected function getCachedResult(array $identifier)
    {
        return $this->getConnectionPool()
            ->getConnectionForTable($this->cacheTable)
            ->select([
                '*',
            ], $this->cacheTable, \array_merge([
                // @TODO I'd prefer to include the pid as well, might be
                // able to provide it through my own enhancer
                'hash' => $this->generateCacheHash(),
            ], $identifier))
            ->fetchAssociative();
    }

    protected function storeCacheResult(string $originalValue, string $resultValue): void
    {
        /** @var \TYPO3\CMS\Core\Database\Connection $connection */
        $connection = $this->getConnectionPool()->getConnectionForTable($this->cacheTable);
        $connection->insert($this->cacheTable, [
            'hash' => $this->generateCacheHash(),
            'slug' => $resultValue,
            'routevar' => $originalValue,
            // @extensionScannerIgnoreLine false positive
            'pid' => (int) $GLOBALS['TSFE']->id,
            'tstamp' => (int) GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp'),
        ]);
    }

    protected function buildPersistenceFieldNames(): array
    {
        return [
            $this->tableAliasPrefix . 'uid',
            $this->tableAliasPrefix . 'pid',
            $this->tableAliasPrefix . $this->routeFieldName,
        ];
    }

    protected function transformRouteValue(?string $value): ?string
    {
        if (empty($this->transformRoute) || $value === null) {
            return $value;
        }

        // @TODO add error handling
        if ((bool) $this->transformRoute['replaceDiacritics']) {
            $value = \str_replace($this->diacritics, $this->diacriticReplacements, $value);
        }
        if ((bool) $this->transformRoute['lowerCase']) {
            $value = \mb_strtolower($value, \mb_detect_encoding($value));
        }
        if (\is_string($this->transformRoute['replacementChar']) && \is_string($this->transformRoute['replacementMatch'])) {
            $value = \preg_replace($this->transformRoute['replacementMatch'], $this->transformRoute['replacementChar'], $value);
        }
        if (\is_string($this->transformRoute['dateTimeFormat'])) {
            $value = (new \DateTime($value))->format($this->transformRoute['dateTimeFormat']);
        }
        if ($this->transformRoute['maxLength'] !== null) {
            $maxLength = (int) $this->transformRoute['maxLength'];
            if (isset($value[$maxLength])) {
                $value = \substr($value, 0, $maxLength);
            }
        }
        return $value;
    }

    protected function ensureUniqueValue(string &$value): string
    {
        $tempValue = $value;
        $counter = 0;
        do {
            $cache = $this->getCachedResult([
                'slug' => $tempValue,
            ]);
        } while (isset($cache['routevar']) && ($tempValue = $value . '-' . ++$counter));
        $value = $tempValue;
        return $value;
    }

    /**
     * Finds value by configurable routing/id fields.
     */
    protected function findByIdentifiers(array $values): ?array
    {
        $constraints = [];
        foreach ($values as $field => $value) {
            $constraints[] = [
                'field' => $field,
                'value' => $value,
            ];
        }
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select(...$this->persistenceFieldNames)->andWhere(...$this->createFieldConstraints($queryBuilder, $constraints));

        /** @var Typo3Version $typo3Version */
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $result = $typo3Version->getMajorVersion() > 10 ? $queryBuilder->executeQuery()->fetchAssociative() : $queryBuilder->execute()->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * @see \TYPO3\CMS\Core\Routing\Aspect\PersistedAliasMapper::createQueryBuilder()
     */
    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable($this->tableName)
            ->from($this->tableName, $this->tableAlias);

        if (!empty($this->tableJoins)) {
            // @TODO error handling
            foreach ($this->tableJoins as $join) {
                $connection = $queryBuilder->getConnection();
                $queryBuilder->add('join', [
                    $connection->quoteIdentifier($join['fromAlias']) => [
                        'joinType' => $join['joinType'] ?? 'inner',
                        'joinTable' => $connection->quoteIdentifier($join['joinTable']),
                        'joinAlias' => $connection->quoteIdentifier($join['joinAlias']),
                        'joinCondition' => $queryBuilder->expr()
                            ->and(...$this->createFieldConstraints($queryBuilder, $join['constraints'])),
                    ],
                ], true);
            }
        }
        if (!empty($this->constraints)) {
            $queryBuilder->where(...$this->createFieldConstraints($queryBuilder, $this->constraints));
        }

        return $queryBuilder;
    }

    protected function createFieldConstraints(QueryBuilder $queryBuilder, array $constraintList): array
    {
        // @TODO error handling
        $constraints = [];
        foreach ($constraintList as $constraint) {
            $operator = \is_string($constraint['operator'] ?? null) && \method_exists($queryBuilder->expr(), $constraint['operator']) ? $constraint['operator'] : 'eq';
            $valueType = null;
            if (\is_string($constraint['valueType'] ?? null)) {
                $valueType = \constant(\TYPO3\CMS\Core\Database\Connection::class . '::PARAM_' . \strtoupper($constraint['valueType']));
            }
            $constraints[] = $queryBuilder->expr()->{$operator}($constraint['field'], isset($constraint['fieldForeign']) && \is_string($constraint['fieldForeign']) ? $queryBuilder->getConnection()
                ->quoteIdentifier($constraint['fieldForeign']) : $queryBuilder->createNamedParameter($constraint['value'] ?? '', $valueType ?? \TYPO3\CMS\Core\Database\Connection::PARAM_STR));
        }
        return $constraints;
    }

    protected function generateCacheHash(): string
    {
        return \md5(\json_encode($this->settings));
    }

    /**
     * @return \TYPO3\CMS\Core\Database\ConnectionPool
     */
    protected function getConnectionPool()
    {
        if ($this->connectionPool === null) {
            $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        }
        return $this->connectionPool;
    }
}
