<?php

namespace FlowCommerce\FlowConnector\Api;

use FlowCommerce\FlowConnector\Api\Data\SyncSkuSearchResultsInterface;
use \FlowCommerce\FlowConnector\Model\SyncSku;

/**
 * Interface SyncSku Management Interface
 * @package FlowCommerce\FlowConnector\Api
 */
interface SyncSkuManagementInterface
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
     * Delete Sync Sku
     * @param SyncSku $syncSku
     * @return void
     */
    public function deleteSyncSku(SyncSku $syncSku);

    /**
     * Returns the next SyncSku batch to be processed
     * @param int $batchSize
     * @return SyncSkuSearchResultsInterface
     */
    public function getNextSyncSkuBatchToBeProcessed($batchSize);

    /**
     * Populates the sync sku queue with all active products on all stores where
     * the flow connector is enabled
     * @return void
     */
    public function enqueueAllProducts();

    /**
     * Marks Sync Sku as done
     * @param SyncSku $syncSku
     * @return SyncSku
     */
    public function markSyncSkuAsDone(SyncSku $syncSku);

    /**
     * Marks Sync Sku as error
     * @param SyncSku $syncSku
     * @param string|null $errorMessage
     * @return SyncSku
     */
    public function markSyncSkuAsError(SyncSku $syncSku, $errorMessage = null);

    /**
     * Marks Sync Sku as processing
     * @param SyncSku $syncSku
     * @return SyncSku
     */
    public function markSyncSkuAsProcessing(SyncSku $syncSku);

    /**
     * Reset any items that have been stuck processing for too long.
     * @return void
     */
    public function resetOldQueueProcessingItems();
}
