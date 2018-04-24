<?php
namespace Innologi\Decosdata\Service\Importer\Parser;
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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Innologi\Decosdata\Service\Importer\Exception\UnreadableImportFile;
use Innologi\Decosdata\Service\Importer\Exception\UnexpectedItemStructure;
use Innologi\Decosdata\Service\Importer\Exception\InvalidValidationFile;
use Innologi\Decosdata\Service\Importer\Exception\ValidationFailed;
use Innologi\Decosdata\Service\Importer\Exception\InvalidItemBlob;
use Innologi\Decosdata\Service\Importer\Exception\InvalidItem;
use Innologi\TraceLogger\TraceLoggerAwareInterface;
use Innologi\TraceLogger\TraceLoggerInterface;
/**
 * Importer Parser: One File Imports, Streaming Parser
 *
 * Parses an import of the type that Decos produces when set to single
 * export files. (default since Decos v4) These can become notoriously
 * large, which causes memory issues if you wish to read these in their
 * entirety at once. Hence this parser parses it as a stream. This
 * enables us to read the import only bit by bit, never creating a
 * large memory footprint. (although a storage handler can still be
 * responsible for that)
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OneFileStreamingParser implements ParserInterface,SingletonInterface,TraceLoggerAwareInterface {
	use \Innologi\TraceLogger\TraceLoggerAware;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \Innologi\Decosdata\Service\Importer\StorageHandler\StorageHandlerInterface
	 * @inject
	 */
	protected $storageHandler;

	/**
	 * @var \Innologi\Decosdata\Domain\Model\Import
	 */
	protected $importObject;

	/**
	 * @var string
	 */
	protected $baseFilePath;

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Processes an import for parsing.
	 *
	 * @param \Innologi\Decosdata\Domain\Model\Import $import
	 * @return void
	 */
	public function processImport(\Innologi\Decosdata\Domain\Model\Import $import) {
		if($this->logger) $this->logger->logTrace();

		$importFilePath = PATH_site . $import->getFile()->getOriginalResource()->getPublicUrl();
		$this->baseFilePath = dirname($importFilePath);
		$this->importObject = $import;

		// attempt validation
		$typoscript = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$validationFilePath = GeneralUtility::getFileAbsFileName($typoscript['importer']['validation']['import']);
		$this->validateImportFile($importFilePath, $validationFilePath);

		// if validation did not throw an exception, start parsing
		$this->storageHandler->initialize($import->getPid());
		$this->startParser($importFilePath);
		$this->storageHandler->commit();
	}

	/**
	 * Validate an import file against a relaxNG formatted XML specification.
	 *
	 * @param string $importFilePath
	 * @param string $rngFilePath
	 * @return boolean
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\UnreadableImportFile
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\InvalidValidationFile
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\ValidationFailed
	 */
	protected function validateImportFile($importFilePath, $rngFilePath) {
		if($this->logger) $this->logger->logTrace();

		$reader = new \XMLReader();
		try {
			if (!@$reader->open($importFilePath)) {
				throw new UnreadableImportFile(1448550537, array($importFilePath));
			}
			if (!@$reader->setRelaxNGSchema($rngFilePath)) {
				throw new InvalidValidationFile(1448550611, array($rngFilePath));
			}

			// skip through the entire import file
			while ($reader->next());
			if (!$reader->isValid()) {
				throw new ValidationFailed(1448550637, array($importFilePath));
			}
		} catch (ValidationFailed $e) {
			throw $e;
		} catch (InvalidValidationFile $e) {
			// does not extend ValidationFailed, hence its own catch
			throw $e;
		} catch (\Exception $e) {
			// pass it on as a ValidationFailed exception
			throw new ValidationFailed(1448550696, array(
				$importFilePath,
				$e->getMessage()
			), 'Validation error in %1$s: %2$s');
		} finally {
			$reader->close();
		}
	}

	/**
	 * Start parser
	 *
	 * @param string $import
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\UnreadableImportFile
	 */
	protected function startParser($importFilePath) {
		if($this->logger) $this->logger->logTrace();

		$reader = new \XMLReader();
		if ( !$reader->open($importFilePath) ) {
			throw new UnreadableImportFile(1448550742, array($importFilePath));
		}

		// place cursor at the first (ITEMS) node
		while ($reader->read() && $reader->nodeType !== \XMLReader::ELEMENT);
		// run through the xml's ITEMS node recursively
		$this->parseItems($reader);

		// close the stream
		$reader->close();
	}

	/**
	 * Parse ITEMS node. This is a recursive process, although not directly
	 * from within this method.
	 *
	 * Note that parentItem is optional, because we have to start somewhere.
	 *
	 * @param \XMLReader $reader The xml parser object
	 * @param mixed $parentItem Type-independent, as this is decided by storageHandler
	 * @return void
	 */
	protected function parseItems(\XMLReader $reader, $parentItem = NULL) {
		if($this->logger && $this->logger->getLevel() > 1) $this->logger->logTrace();

		$type = strtoupper($reader->getAttribute('TYPE'));
		// place cursor at the next element (regardless of depth) and get new depth
		while ($reader->read() && $reader->nodeType != \XMLReader::ELEMENT);
		$itemDepth = $reader->depth;

		switch ($type) {
			case 'BLOB':
				// for each ItemBlob node
				while (($reader->nodeType === \XMLReader::ELEMENT || $reader->read()) && $itemDepth === $reader->depth) {
					try {
						$this->parseItemBlob($reader, $parentItem);
					} catch (InvalidItemBlob $e) {
						$this->errors[] = $e->getFormattedErrorMessage();
					}
				}
				break;
			default:
				// for every other Item node
				while (($reader->nodeType === \XMLReader::ELEMENT || $reader->read()) && $itemDepth === $reader->depth) {
					try {
						$this->parseItem($reader, $type, $parentItem);
					} catch (InvalidItem $e) {
						$this->errors[] = $e->getFormattedErrorMessage();
					}
				}
		}
	}

	/**
	 * Parse ITEM node. If an item contains sub-items, will call parseItems, starting
	 * a recursion of the entire process.
	 *
	 * Note that parentItem is optional, because we have to start somewhere.
	 *
	 * @param \XMLReader $reader
	 * @param string $itemType
	 * @param mixed $parentItem
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\UnexpectedItemStructure
	 */
	protected function parseItem(\XMLReader $reader, $itemType, $parentItem = NULL) {
		if($this->logger && $this->logger->getLevel() > 1) $this->logger->logTrace();

		$item = NULL;

		// place cursor at the next node (regardless of depth) and get new depth
		while ($reader->read() && $reader->nodeType !== \XMLReader::ELEMENT);
		$fieldDepth = $reader->depth;

		// for each child-node of ITEM
		while (($reader->nodeType === \XMLReader::ELEMENT || $reader->read()) && $fieldDepth === $reader->depth) {
			$fieldName = strtoupper($reader->name);
			switch ($fieldName) {
				case 'ITEM_KEY':
					$itemKey = $this->readNodeValue($reader);
					$item = $this->storageHandler->pushItem(array(
						'item_key' => $itemKey,
						'item_type' => $itemType,
						'import' => array($this->importObject),
						'parent_item' => $parentItem
					));
					break;
				case 'ITEMS':
					if ($item === NULL) {
						throw new UnexpectedItemStructure(1448550765, array($fieldName));
					}
					// recursive call to run through child ITEMS-node and its subsequent children
					$this->parseItems($reader, $item);
					break;
				// for any other node:
				default:
					if ($item === NULL) {
						// @LOW if this ever becomes an issue, I should consider storing fields + values in an array first
						throw new UnexpectedItemStructure(1448550787, array($fieldName));
					}
					$this->storageHandler->pushItemField(array(
						'item' => $item,
						'field' => $fieldName,
						'field_value' => $this->readNodeValue($reader)
					));
			}
		}
	}

	/**
	 * Parse ItemBlob node. An ItemBlob is like any other Item, except they always
	 * represent a file, which we process differently. Hence this method is a
	 * variation of parseItem()
	 *
	 * @param \XMLReader $reader
	 * @param mixed $parentItem
	 * @return void
	 */
	protected function parseItemBlob(\XMLReader $reader, $parentItem) {
		if($this->logger && $this->logger->getLevel() > 1) $this->logger->logTrace();

		$data = array();
		$fallback = NULL;

		// place cursor at the next element (regardless of depth) and get new depth
		while ($reader->read() && $reader->nodeType !== \XMLReader::ELEMENT);
		$fieldDepth = $reader->depth;

		// for each child-node of ITEM
		while (($reader->nodeType === \XMLReader::ELEMENT || $reader->read()) && $fieldDepth === $reader->depth) {
			$fieldName = strtoupper($reader->name);
			switch ($fieldName) {
				case 'ITEM_KEY':
					$data['item_key'] = $this->readNodeValue($reader);
					break;
				case 'SEQUENCE':
					// @TODO is there ever any use-case for maintaining multiple file versions?
						// maybe we can introduce a setting that allows you to only keep the latest file version
						// it would allow us to make the related query parts quite a bit simpler
						// it would also allow us to remove both decos data and files and FAL data, decreasing the footprint of the database
					$data['sequence'] = $this->readNodeValue($reader);
					break;
				case 'FILEPATH':
					// determine complete relative path
					$data['filepath'] = GeneralUtility::fixWindowsFilePath($this->baseFilePath . $this->readNodeValue($reader));
					break;
				case 'DOCUMENT_DATE':
					// fallback for older decos exports
					$fallback = $this->readNodeValue($reader);
					break;
				default:
					// do nothing for any other node except move pointer
					while ($reader->nodeType !== \XMLReader::END_ELEMENT && $reader->read());
			}
		}

		// fallback for older decos exports that didn't have sequence fields but did have document dates
		if (!(isset($data['sequence']) || $fallback === NULL)) {
			$data['sequence'] = strtotime($fallback);
		}

		$data['item'] = $parentItem;
		$this->storageHandler->pushItemBlob($data);
	}

	/**
	 * Read through and return a complete XML node value (trimmed).
	 * If trimmed value is an empty string, returns NULL instead.
	 *
	 * Note that this moves $reader's pointer! It is to be used only
	 * once per node!
	 *
	 * @param \XMLReader $reader
	 * @return string|NULL
	 */
	protected function readNodeValue(\XMLReader $reader) {
		$value = '';
		while ($reader->read()) {
			$nodeType = $reader->nodeType;
			switch ($nodeType) {
				case \XMLReader::TEXT:
					$value .= $reader->value;
					break;
				case \XMLReader::END_ELEMENT:
					break 2;
				default:
			}
		}
		$value = trim($value);
		return isset($value[0]) ? $value : NULL;
	}

	/**
	 * Returns any parsing errors
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * {@inheritDoc}
	 * @see TraceLoggerAwareInterface::setLogger()
	 */
	public function setLogger(TraceLoggerInterface $logger): void {
		$this->logger = $logger;
		if ($this->storageHandler instanceof TraceLoggerAwareInterface) {
			$this->storageHandler->setLogger($logger);
		}
	}

}
