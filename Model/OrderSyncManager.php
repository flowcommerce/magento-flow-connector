<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Model\SyncManager;
use FlowCommerce\FlowConnector\Model\Order as FlowOrder;
use FlowCommerce\FlowConnector\Model\Allocation as FlowAllocation;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class OrderSyncManager
 * @package FlowCommerce\FlowConnector\Model
 */
class OrderSyncManager
{
    /*
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var FlowOrder
     */
    private $flowOrder;

    /**
     * @var FlowAllocation
     */
    private $flowAllocation;

    /**
     * @param SyncManager $syncManager
     * @param Notification $notification
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param StoreManager $storeManager
     * @param FlowOrder $flowOrder
     * @param FlowAllocation $flowAllocation
     */
    public function __construct(
        SyncManager $syncManager,
        Notification $notification,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        FlowOrder $flowOrder,
        FlowAllocation $flowAllocation,
        StoreManager $storeManager
    ) {
        $this->syncManager = $syncManager;
        $this->notification = $notification;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->flowOrder = $flowOrder;
        $this->flowAllocation = $flowAllocation;
    }

    /**
     * Set the logger (used by console command).
     * @param Logger $logger
     * @return void
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        $this->notification->setLogger($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->logger->info('Starting processing order sync poll');

        foreach ($this->storeManager->getStores() as $store) {
            $pendingOrderRecords = $this->syncManager->getSyncStreamPendingRecordByKey($store->getId(), $this->syncManager::PLACED_ORDER_TYPE);
            if ((int) count($pendingOrderRecords) === 0) {
                $this->logger->info('No orders found to process.');
                return false;
            }

            foreach ($pendingOrderRecords as $pendingOrderRecord) {
                $this->logger->info('Processing pending Flow order number: ' . $pendingOrderRecord['value']);
                $order = $this->flowOrder->getByNumber($store->getId(), $pendingOrderRecord['value']);
                $allocation = $this->flowAllocation->getByNumber($store->getId(), $pendingOrderRecord['value']);
                $this->webhookEvent->processOrderPlacedPayloadData([
                    'allocation' => $allocation,
                    'order' => $order
                ], false);
            }
        }
        $this->logger->info('Done processing order sync poll');
        return true;
    }
}
