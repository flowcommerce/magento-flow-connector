<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use FlowCommerce\FlowConnector\Api\InventorySyncManagementInterface as InventorySyncManager;

/**
 * Observer class to queue inventory changes.
 * @package FlowCommerce\FlowConnector\Observer
 */
class FlowInventoryUpdateObserver implements ObserverInterface
{
    /**
     * @var InventorySyncManager
     */
    private $inventorySyncManager;

    /**
     * FlowInventoryUpdateObserver constructor.
     * @param InventorySyncManager $inventorySyncManager
     */
    public function __construct(
        InventorySyncManager $inventorySyncManager
    ) {
        $this->inventorySyncManager = $inventorySyncManager;
    }

    /**
     * This observer triggers after a stock item save and queues the product
     * for syncing to Flow.
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var StockItemInterface $stockItem */
        $stockItem = $observer->getItem();
        $this->inventorySyncManager->enqueueStockItem($stockItem);
    }
}
