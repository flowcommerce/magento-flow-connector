<?php

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use FlowCommerce\FlowConnector\Api\Data\SyncOrderInterface;
use FlowCommerce\FlowConnector\Api\SyncOrderManagementInterface;
use FlowCommerce\FlowConnector\Model\Allocation as FlowAllocation;
use FlowCommerce\FlowConnector\Model\Order as FlowOrder;
use FlowCommerce\FlowConnector\Model\SyncManager;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class SyncOrderManagement
 * @package FlowCommerce\FlowConnector\Model
 */
class SyncOrderManager implements SyncOrderManagementInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /*
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @var FlowOrder
     */
    private $flowOrder;

    /**
     * @var FlowAllocation
     */
    private $flowAllocation;

    /**
     * @var WebhookEvent
     */
    private $webhookEvent;

    /**
     * SyncOrderManagement constructor.
     * @param Logger $logger
     * @param StoreManager $storeManager
     * @param WebhookEvent $webhookEvent
     * @param SyncManager $syncManager
     * @param FlowOrder $flowOrder
     * @param FlowAllocation $flowAllocation
     */
    public function __construct(
        Logger $logger,
        StoreManager $storeManager,
        WebhookEvent $webhookEvent,
        SyncManager $syncManager,
        FlowOrder $flowOrder,
        FlowAllocation $flowAllocation
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->webhookEvent = $webhookEvent;
        $this->syncManager = $syncManager;
        $this->flowOrder = $flowOrder;
        $this->flowAllocation = $flowAllocation;
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
                if ($this->webhookEvent->getOrderByFlowOrderNumber($pendingOrderRecord['value'])) {
                    $this->logger->info('Flow order number: ' . $pendingOrderRecord['value'] . ' already imported.');
                    $this->syncManager->putSyncStreamRecord($store->getId(), $this->syncManager::PLACED_ORDER_TYPE, $pendingOrderRecord['value']);
                    continue;
                }
                $this->syncByValue($pendingOrderRecord['value'], $store->getId());
            }
        }
        $this->logger->info('Done processing order sync poll');
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function failByValue(string $orderNumber, string $reason, array $messages, int $storeId)
    {
        $this->syncManager->postSyncStreamRecordFailure($storeId, $this->syncManager::PLACED_ORDER_TYPE, $orderNumber, $reason, $messages);
        $this->webhookEvent->addSyncOrderFailure($orderNumber, $storeId);
    }

    /**
     * {@inheritdoc}
     */
    public function syncByValue(string $orderNumber, int $storeId)
    {
        $order = $this->flowOrder->getByNumber($storeId, $orderNumber);
        $allocation = $this->flowAllocation->getByNumber($storeId, $orderNumber);
        $this->webhookEvent->processOrderPlacedPayloadData([
            'allocation' => $allocation,
            'order' => $order
        ], false);
    }

    /**
     * Allows logger to be overridden
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
}
