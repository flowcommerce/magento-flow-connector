<?php

namespace FlowCommerce\FlowConnector\Api;

use FlowCommerce\FlowConnector\Api\Data\InventorySyncSearchResultsInterface;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * Interface InventorySync Management Interface
 * @package FlowCommerce\FlowConnector\Api
 */
interface InventorySyncManagementInterface
{
    /**
     * Deletes old processed items.
     * @return void
     */
    public function deleteOldQueueDoneItems();

    /**
     * Deletes items with errors where there is a new record that is done.
     * @return void
     */
    public function deleteQueueErrorDoneItems();

    /**
     * Populates the inventory Sync queue with all active stock items on all stores where
     * the flow connector is enabled
     * @return void
     */
    public function enqueueAllStockItems();

    /**
     * Enqueues given stock item to be synced with flow's inventory API
     * @param StockItemInterface $stockItem
     * @return mixed
     */
    public function enqueueStockItem(StockItemInterface $stockItem);

    /**
     * Processes the inventory sync queue
     * @param int $numToProcess
     * @param int $keepAlive
     * @return InventorySync
     */
    public function process($numToProcess = 1000, $keepAlive = 60);

    /**
     * Marks Inventory Sync entry as done
     * @param InventorySync $inventorySync
     * @return InventorySync
     */
    public function markInventorySyncAsDone(InventorySync $inventorySync);

    /**
     * Marks Inventory Sync entry as error
     * @param InventorySync $inventorySync
     * @param string|null $errorMessage
     * @return InventorySync
     */
    public function markInventorySyncAsError(InventorySync $inventorySync, $errorMessage = null);

    /**
     * Marks Inventory Sync entry as processing
     * @param InventorySync $inventorySync
     * @return InventorySync
     */
    public function markInventorySyncAsProcessing(InventorySync $inventorySync);

    /**
     * Reset any entries that have been stuck processing for too long.
     * @return void
     */
    public function resetOldQueueProcessingItems();
}
