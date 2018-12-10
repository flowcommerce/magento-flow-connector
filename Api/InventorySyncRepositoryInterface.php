<?php

namespace FlowCommerce\FlowConnector\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Inventory Sync CRUD Interface
 * @package FlowCommerce\FlowConnector\Api
 */
interface InventorySyncRepositoryInterface
{
    /**
     * Save Inventory Sync.
     * @param InventorySyncInterface $inventorySync
     * @return InventorySyncInterface
     * @throws CouldNotSaveException
     */
    public function save(InventorySyncInterface $inventorySync);

    /**
     * Save multiple Inventory Syncs.
     * @param InventorySyncInterface[] $inventorySyncs
     * @return void
     * @throws CouldNotSaveException
     */
    public function saveMultiple(array $inventorySyncs);

    /**
     * Retrieve Inventory Sync by given identifier.
     * @param int $inventorySyncId
     * @return InventorySyncInterface
     * @throws LocalizedException
     */
    public function getById($inventorySyncId);

    /**
     * Retrieve Inventory Syncs matching the specified criteria.
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete Inventory Sync.
     * @param InventorySyncInterface $inventorySync
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function delete(InventorySyncInterface $inventorySync);

    /**
     * Delete Inventory Sync by ID.
     * @param int $inventorySyncId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($inventorySyncId);

    /**
     * Delete multiple Inventory Syncs
     * @param InventorySyncInterface[] $inventorySyncs
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteMultiple(array $inventorySyncs);

    /**
     * Delete queue error items where there is another inventory sync entry present in the table which happened after
     * the error
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteQueueErrorDoneItems();

    /**
     * Defers update of multiple inventory sync's statuses to the inventory sync resource model
     * the error
     * @param InventorySyncInterface[] $inventorySyncs
     * @param string $newStatus
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function updateMultipleStatuses(array $inventorySyncs, $newStatus);

    /**
     * Delete Inventory Syncs by Store ID.
     * @param int $storeId
     * @return bool true on success
     * @throws CouldNotDeleteException
     */
    public function deleteByStoreId(int $storeId);
}
