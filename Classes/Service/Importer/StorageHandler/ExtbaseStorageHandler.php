<?php
namespace Innologi\Decosdata\Service\Importer\StorageHandler;
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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use Innologi\Decosdata\Domain\Factory\FieldFactory;
use Innologi\Decosdata\Domain\Factory\ItemTypeFactory;
use Innologi\Decosdata\Domain\Factory\ItemBlobFactory;
use Innologi\Decosdata\Domain\Factory\ItemFieldFactory;
use Innologi\Decosdata\Domain\Factory\ItemFactory;
use Innologi\Decosdata\Domain\Repository\ItemBlobRepository;
use Innologi\Decosdata\Domain\Repository\ItemFieldRepository;
use Innologi\Decosdata\Domain\Repository\ItemRepository;
use Innologi\Decosdata\Exception\MissingObjectProperty;
use Innologi\Decosdata\Service\Importer\Exception\InvalidItemBlob;
use Innologi\Decosdata\Service\Importer\Exception\InvalidItem;
use Innologi\TYPO3FalApi\Exception\FileException;
/**
 * Importer Storage Handler: Extbase Edition
 *
 * Handles storage of parsed import via Extbase Domain Models, Repositories, Factories
 * and the PersistenceManager. Note that while this is the correct way to implement
 * storage handling, it is just not realistic to use performance-wise, in combination
 * with the OneFileStreamingParser (default).
 *
 * Its performance impact compared to the classic storage handler was measured to be
 * a factor of 3.1
 *
 * @package decosdata
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ExtbaseStorageHandler implements StorageHandlerInterface,SingletonInterface {

	/**
	 * @var ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @var ItemRepository
	 */
	protected $itemRepository;

	/**
	 * @var ItemFieldRepository
	 */
	protected $itemFieldRepository;

	/**
	 * @var ItemBlobRepository
	 */
	protected $itemBlobRepository;

	/**
	 * @var ItemFactory
	 */
	protected $itemFactory;

	/**
	 * @var ItemFieldFactory
	 */
	protected $itemFieldFactory;

	/**
	 * @var ItemBlobFactory
	 */
	protected $itemBlobFactory;

	/**
	 * @var ItemTypeFactory
	 */
	protected $itemTypeFactory;

	/**
	 * @var FieldFactory
	 */
	protected $fieldFactory;

	/**
	 * @var QuerySettingsInterface
	 */
	protected $defaultQuerySettings;

	/**
	 *
	 * @param ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
	{
		$this->configurationManager = $configurationManager;
	}

	/**
	 *
	 * @param PersistenceManager $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(PersistenceManager $persistenceManager)
	{
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 *
	 * @param ItemRepository $itemRepository
	 * @return void
	 */
	public function injectItemRepository(ItemRepository $itemRepository)
	{
		$this->itemRepository = $itemRepository;
	}

	/**
	 *
	 * @param ItemFieldRepository $itemFieldRepository
	 * @return void
	 */
	public function injectItemFieldRepository(ItemFieldRepository $itemFieldRepository)
	{
		$this->itemFieldRepository = $itemFieldRepository;
	}

	/**
	 *
	 * @param ItemBlobRepository $itemBlobRepository
	 * @return void
	 */
	public function injectItemBlobRepository(ItemBlobRepository $itemBlobRepository)
	{
		$this->itemBlobRepository = $itemBlobRepository;
	}

	/**
	 *
	 * @param ItemFactory $itemFactory
	 * @return void
	 */
	public function injectItemFactory(ItemFactory $itemFactory)
	{
		$this->itemFactory = $itemFactory;
	}

	/**
	 *
	 * @param ItemFieldFactory $itemFieldFactory
	 * @return void
	 */
	public function injectItemFieldFactory(ItemFieldFactory $itemFieldFactory)
	{
		$this->itemFieldFactory = $itemFieldFactory;
	}

	/**
	 *
	 * @param ItemBlobFactory $itemBlobFactory
	 * @return void
	 */
	public function injectItemBlobFactory(ItemBlobFactory $itemBlobFactory)
	{
		$this->itemBlobFactory = $itemBlobFactory;
	}

	/**
	 *
	 * @param ItemTypeFactory $itemTypeFactory
	 * @return void
	 */
	public function injectItemTypeFactory(ItemTypeFactory $itemTypeFactory)
	{
		$this->itemTypeFactory = $itemTypeFactory;
	}

	/**
	 *
	 * @param FieldFactory $fieldFactory
	 * @return void
	 */
	public function injectFieldFactory(FieldFactory $fieldFactory)
	{
		$this->fieldFactory = $fieldFactory;
	}

	/**
	 *
	 * @param QuerySettingsInterface $defaultQuerySettings
	 * @return void
	 */
	public function injectDefaultQuerySettings(QuerySettingsInterface $defaultQuerySettings)
	{
		$this->defaultQuerySettings = $defaultQuerySettings;
	}

	/**
	 * Initialize Storage Handler
	 *
	 * This will allow the importer to set specific parameters
	 * that are of importance.
	 *
	 * @param integer $pid
	 * @return void
	 */
	public function initialize($pid) {
		$this->configureStoragePid($pid);
		$this->configureQuerySettings($pid);
	}

	/**
	 * Configures storage pid
	 *
	 * This will allow the importer to set the pid for newly created records
	 * to that of the import record.
	 *
	 * @param integer $pid
	 * @return void
	 */
	protected function configureStoragePid($pid) {
		// @LOW ___note that if the importer is ever used in a plugin or module context and doesn't respect storagePid, yet this importer is followed up with different actions within the same request that DO respect storagePid, we might have to revert the value
		// by setting the storagePid this way, it is respected by both QuerySettings _and_ PersistenceManager
		$this->configurationManager->setConfiguration(
			array_merge(
				$this->configurationManager->getConfiguration(
					ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
				),
				[
					'persistence' => [
						'storagePid' => $pid
					]
				]
			)
		);
		// this persists AutoInserted records more quickly
		$this->itemTypeFactory->setStoragePid($pid);
		$this->fieldFactory->setStoragePid($pid);
	}

	/**
	 * Configures query settings
	 *
	 * Some parameters can be set only through the query settings.
	 * It's more efficient to set them as default rather than from
	 * within the repository methods.
	 *
	 * @param integer $pid
	 * @return void
	 */
	protected function configureQuerySettings($pid) {
		// during import, finding items / itemblobs / itemfields should happen regardless
		$this->defaultQuerySettings->getEnableFieldsToBeIgnored(TRUE);
		// configureStoragePid() suffices, but this speeds up findBy* methods somewhat
		$this->defaultQuerySettings->setStoragePageIds([$pid]);

		$this->itemRepository->setDefaultQuerySettings($this->defaultQuerySettings);
		$this->itemFieldRepository->setDefaultQuerySettings($this->defaultQuerySettings);
		$this->itemBlobRepository->setDefaultQuerySettings($this->defaultQuerySettings);
	}

	/**
	 * Push an item ready for commit.
	 *
	 * @param array $data
	 * @return \Innologi\Decosdata\Domain\Model\Item
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\InvalidItem
	 */
	public function pushItem(array $data) {
		try {
			/* @var $parentItem \Innologi\Decosdata\Domain\Model\Item */
			$parentItem = $data['parent_item'];
			unset($data['parent_item']);

			$data['item_type'] = $this->itemTypeFactory->getByItemType($data['item_type'], TRUE);
			$item = $this->itemFactory->getByItemKey($data['item_key'], $data);

			if ($item->_isNew()) {
				if ($parentItem !== NULL) {
					// adds item with parent/child item relations set when $parentItem is persisted
					$parentItem->addChildItem($item);
				} else {
					$this->itemRepository->add($item);
				}
			} else {
				$this->itemRepository->update($item);
			}

			return $item;
		} catch (MissingObjectProperty $e) {
			throw new InvalidItem($e->getCode(), [
				$data['item_key'], $e->getMessage()
			]);
		}
	}

	/**
	 * Push an itemblob ready for commit.
	 *
	 * @param array $data
	 * @return void
	 * @throws \Innologi\Decosdata\Service\Importer\Exception\InvalidItemBlob
	 */
	public function pushItemBlob(array $data) {
		try {
			if (! (isset($data['filepath'][0]) && is_file($data['filepath'])) ) {
				// filepath missing or not a file
				throw new FileException(1448551092, [$data['filepath'] ?? 'NULL']);
			}

			/* @var $parentItem \Innologi\Decosdata\Domain\Model\Item */
			$parentItem = $data['item'];
			unset($data['item']);

			$itemBlob = $this->itemBlobFactory->getByItemKey($data['item_key'], $data);
			if ($itemBlob->_isNew()) {
				// adds blob with item-relation when $parentItem is persisted
				$parentItem->addItemBlob($itemBlob);
			} else {
				$this->itemBlobRepository->update($itemBlob);
			}
		} catch (FileException $e) {
			// if there is no correct file, there is no valid item blob
			throw new InvalidItemBlob($e->getCode(), [
				$data['item_key'], $e->getMessage()
			]);
		} catch (MissingObjectProperty $e) {
			throw new InvalidItemBlob($e->getCode(), [
				$data['item_key'], $e->getMessage()
			]);
		}
	}

	/**
	 * Push an itemfield ready for commit.
	 *
	 * @param array $data
	 * @return void
	 */
	public function pushItemField(array $data) {
		/* @var $parentItem \Innologi\Decosdata\Domain\Model\Item */
		$parentItem = $data['item'];
		unset($data['item']);

		$data['field'] = $this->fieldFactory->getByFieldName($data['field'], TRUE);
		$itemField = $this->itemFieldRepository->findOneByFieldAndItem($data['field'], $parentItem);

		if ($itemField === NULL) {
			// if no itemfield and no fieldvalue, nothing will happen
			if ($data['field_value'] !== NULL) {
				// no itemfield found and new value exists: add new itemfield
				$itemField = $this->itemFieldFactory->create($data);
				// adds itemField with item relation set when $parentItem is persisted
				$parentItem->addItemField($itemField);
			}
		} elseif ($data['field_value'] === NULL) {
			// itemfield found but new value is empty: remove existing itemfield
			$parentItem->removeItemField($itemField);
			$this->itemFieldRepository->remove($itemField);
		} elseif ($data['field_value'] !== $itemField->getFieldValue()) {
			// itemfield found but value has changed: update existing itemfield
			$itemField->setFieldValue($data['field_value']);
			$this->itemFieldRepository->update($itemField);
		}
	}

	/**
	 * Commits all pushed data.
	 * (clearing any remaining object-references thus reducing memory footprint)
	 *
	 * The importer does not necessarily get called from extbase bootstrap, which provides
	 * a call to persistAll() on destruct. Hence we have to call it manually, preferably
	 * per import.
	 *
	 * @return void
	 */
	public function commit() {
		// @TODO what if an import is huge? Why wait this long to persist and still allow huge memory consumption? Perhaps review the placement of commit()!
		$this->persistenceManager->persistAll();
	}

}
