<?php
namespace Innologi\Decospublisher7\Service\Importer\Parser;
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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use Innologi\Decospublisher7\Service\Importer\Exception\UnreadableImportFile;
use Innologi\Decospublisher7\Service\Importer\Exception\UnexpectedItemStructure;
use Innologi\Decospublisher7\Service\Importer\Exception\InvalidValidationFile;
use Innologi\Decospublisher7\Service\Importer\Exception\ValidationFailed;
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
 * @package decospublisher7
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OneFileStreamingParser implements ParserInterface,SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \Innologi\Decospublisher7\Service\Importer\StorageHandler\StorageHandlerInterface
	 * @inject
	 */
	protected $storageHandler;

	/**
	 * @var \Innologi\Decospublisher7\Domain\Model\Import
	 */
	protected $importObject;

	/**
	 * @var string
	 */
	protected $baseFilePath;

	/**
	 * Exception message strings I don't immediately plan on putting in llang files
	 *
	 * @var array
	 */
	protected $lang = array(
		'UnreadablePath' => 'The path \'%1$s\' is either not a file, unreadable, or of an unexpected format.',
		'ValidationFailedUnknown' => 'Validation error in %1$s: %2$s'
	);

	/**
	 * Processes an import for parsing.
	 *
	 * @param \Innologi\Decospublisher7\Domain\Model\Import $import
	 * @return void
	 */
	public function processImport(\Innologi\Decospublisher7\Domain\Model\Import $import) {
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
	 * @throws \Innologi\Decospublisher7\Service\Importer\Exception\UnreadableImportFile
	 * @throws \Innologi\Decospublisher7\Service\Importer\Exception\InvalidValidationFile
	 * @throws \Innologi\Decospublisher7\Service\Importer\Exception\ValidationFailed
	 */
	protected function validateImportFile($importFilePath, $rngFilePath) {
		$reader = new \XMLReader();
		try {
			if (!@$reader->open($importFilePath)) {
				throw new UnreadableImportFile(array(
					sprintf($this->lang['UnreadablePath'], $importFilePath)
				));
			}
			if (!@$reader->setRelaxNGSchema($rngFilePath)) {
				throw new InvalidValidationFile(array(
					sprintf($this->lang['UnreadablePath'], $rngFilePath)
				));
			}

			// skip through the entire import file
			while ($reader->next());
			if (!$reader->isValid()) {
				throw new ValidationFailed(array($importFilePath));
			}
		} catch (ValidationFailed $e) {
			throw $e;
		} catch (InvalidValidationFile $e) {
			// does not extend ValidationFailed, hence its own catch
			throw $e;
		} catch (\Exception $e) {
			// pass it on as a ValidationFailed exception
			throw new ValidationFailed(array(
				$importFilePath,
				$e->getMessage()
			), $this->lang['ValidationFailedUnknown']);
		} finally {
			$reader->close();
		}
	}

	/**
	 * Start parser
	 *
	 * @param string $import
	 * @return void
	 * @throws \Innologi\Decospublisher7\Service\Importer\Exception\UnreadableImportFile
	 */
	protected function startParser($importFilePath) {
		$reader = new \XMLReader();
		if ( !$reader->open($importFilePath) ) {
			throw new UnreadableImportFile(array(
				sprintf($this->lang['UnreadablePath'], $importFilePath)
			));
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
	 * @param XMLReader $reader The xml parser object
	 * @param mixed $parentItem Type-independent, as this is decided by storageHandler
	 * @return void
	 */
	protected function parseItems(\XMLReader $reader, $parentItem = NULL) {
		$type = strtoupper($reader->getAttribute('TYPE'));
		// place cursor at the next element (regardless of depth) and get new depth
		while ($reader->read() && $reader->nodeType != \XMLReader::ELEMENT);
		$itemDepth = $reader->depth;

		switch ($type) {
			case 'BLOB':
				// for each ItemBlob node
				while (($reader->nodeType === \XMLReader::ELEMENT || $reader->read()) && $itemDepth === $reader->depth) {
					$this->parseItemBlob($reader, $parentItem);
				}
				break;
			default:
				// for every other Item node
				while (($reader->nodeType === \XMLReader::ELEMENT || $reader->read()) && $itemDepth === $reader->depth) {
					$this->parseItem($reader, $type, $parentItem);
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
	 * @throws \Innologi\Decospublisher7\Service\Importer\Exception\UnexpectedItemStructure
	 */
	protected function parseItem(\XMLReader $reader, $itemType, $parentItem = NULL) {
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
						throw new UnexpectedItemStructure(array($fieldName));
					}
					// recursive call to run through child ITEMS-node and its subsequent children
					$this->parseItems($reader, $item);
					break;
				// for any other node:
				default:
					if ($item === NULL) {
						// @LOW if this ever becomes an issue, I should consider storing fields + values in an array first
						throw new UnexpectedItemStructure(array($fieldName));
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
		$data = array();

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
					$data['sequence'] = $this->readNodeValue($reader);
					break;
				case 'FILEPATH':
					// determine complete relative path
					$data['filepath'] = GeneralUtility::fixWindowsFilePath($this->baseFilePath . $this->readNodeValue($reader));
					break;
				default:
					// do nothing for any other node except move pointer
					while ($reader->nodeType !== \XMLReader::END_ELEMENT && $reader->read());
			}
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
}
