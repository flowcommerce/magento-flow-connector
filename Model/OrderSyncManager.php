<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Model\OrderSync;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class OrderSyncManager
 * @package FlowCommerce\FlowConnector\Model
 */
class OrderSyncManager
{
    /*
     * @var OrderSync
     */
    private $orderSync;

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
     * @param OrderSync $orderSync
     * @param Notification $notification
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param StoreManager $storeManager
     */
    public function __construct(
        OrderSync $orderSync,
        Notification $notification,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        StoreManager $storeManager
    ) {
        $this->orderSync = $orderSync;
        $this->notification = $notification;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
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
                $this->logger->info('Processing pending Flow order number: ' . $pendingOrderRecord['value']));
                // TODO create order get by number
                $pendingOrderData = $this->orderGetByNumber->execute($pendingOrderRecord['value']);
                // TODO create allocation get by number
                $pendingOrderAllocationData = $this->allocationGetByNumber->execute($pendingOrderRecord['value']);
                $this->webhookEvent->processOrderPlacedPayloadData([
                    'allocation' => $pendingOrderAllocationData,
                    'order' => $pendingOrderData
                ], false);
            }
        }
        $this->logger->info('Done processing order sync poll');
        return true;
    }
}
