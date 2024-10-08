<?php

namespace Innologi\Decosdata\Service\Importer;

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
use Innologi\Decosdata\Domain\Repository\ImportRepository;
use Innologi\Decosdata\Service\Importer\Parser\ParserInterface;
use Innologi\Decosdata\Utility\DebugUtility;
use Innologi\TraceLogger\TraceLoggerAwareInterface;
use Innologi\TraceLogger\TraceLoggerInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Importer Service
 *
 * Imports Decos XML Imports.
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ImporterService implements SingletonInterface, TraceLoggerAwareInterface
{
    use \Innologi\TraceLogger\TraceLoggerAware;

    /**
     * @var ImportRepository
     */
    protected $importRepository;

    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var string
     */
    protected $sitePath;

    public function injectImportRepository(ImportRepository $importRepository): void
    {
        $this->importRepository = $importRepository;
    }

    public function injectParser(ParserInterface $parser): void
    {
        $this->parser = $parser;
    }

    /**
     * Will process all available imports, regardless of page uid
     */
    public function importAll($force = false): void
    {
        $importCollection = $this->importRepository->findAllEverywhere();
        $this->importSelection($importCollection, $force);
    }

    /**
     * Will process a selection of imports given as parameter as uid
     */
    public function importUidSelection(array $uidArray, $force = false): void
    {
        if ($this->logger) {
            $this->logger->logTrace();
        }
        $importCollection = $this->importRepository->findInUidEverywhere($uidArray);
        $this->importSelection($importCollection, $force);
    }

    /**
     * Will process a selection of imports given as parameter
     *
     * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array $importCollection
     */
    public function importSelection($importCollection, $force = false): void
    {
        if ($this->logger) {
            $this->logger->logTrace();
        }
        /** @var \Innologi\Decosdata\Domain\Model\Import $import */
        foreach ($importCollection as $import) {
            try {
                $this->importSingle($import, $force);
            } catch (Exception\ImporterError $e) {
                // register the error and move on
                $this->errors[$import->getUid() . ':' . $import->getTitle()] = $e->getFormattedErrorMessage();
            }
            // any other exception is so serious that we have to halt the entire process anyway
        }
    }

    /**
     * Will process a single import
     *
     * @throws
     */
    public function importSingle(\Innologi\Decosdata\Domain\Model\Import $import, $force = false): void
    {
        if ($this->logger) {
            $this->logger->logTrace();
        }
        $filePath = $this->getSitePath() . $import->getFile()->getOriginalResource()->getPublicUrl();
        if (!file_exists($filePath) || (($newHash = $this->getHashIfReadyForProcessing($filePath, $import->getHash())) === false && !$force)) {
            // @LOW consider throwing an exception, which when caught will register the import as notUpdated?
            // either no file present, or the file has seen no change: no sense in continuing
            return;
        }

        $this->parser->processImport($import);
        // not using the File record's hash, because that one is set initially and could be updated by outside sources
        if ($newHash !== false) {
            $import->setHash($newHash);
        }
        // mark as processed
        $this->importRepository->update($import);

        // parsing errors throw an exception afterwards
        $errors = $this->parser->getErrors();
        if (!empty($errors)) {
            throw new Exception\ImporterError(
                1448551145,
                null,
                DebugUtility::formatArrayValues($errors),
            );
        }
    }

    /**
     * @return string
     */
    protected function getSitePath()
    {
        if ($this->sitePath === null) {
            $this->sitePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
        }
        return $this->sitePath;
    }

    /**
     * Returns hash of $filePath, only if it does not match $knownHash.
     * Returns boolean FALSE if file does not exist or hash matches $knownHash.
     *
     * @param string $filePath
     * @param string $knownHash
     * @return string|boolean
     */
    protected function getHashIfReadyForProcessing($filePath, $knownHash)
    {
        if ($this->logger) {
            $this->logger->logTrace();
        }
        return ($newHash = md5_file($filePath)) !== $knownHash ? $newHash : false;
    }

    /**
     * Returns any errors registered by the service
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @see TraceLoggerAwareInterface::setLogger()
     */
    public function setLogger(TraceLoggerInterface $logger): void
    {
        $this->logger = $logger;
        if ($this->parser instanceof TraceLoggerAwareInterface) {
            $this->parser->setLogger($logger);
        }
    }
}
