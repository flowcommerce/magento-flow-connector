<?php

namespace FlowCommerce\FlowConnector\Observer;

use Exception;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Observer class to queue product deletions.
 */
class FlowProductDeleteObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * FlowProductDeleteObserver constructor.
     * @param Logger $logger
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        Logger $logger,
        SyncSkuManager $syncSkuManager
    ) {
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * This observer triggers after a catalog product delete and queues the sku
     * for deletion from Flow.
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getProduct();

        if ($product) {
            try {
                $this->syncSkuManager->enqueue($product);
            } catch (Exception $e) {
                $this->logger->error(
                    'Flow Catalog Sync - An error happened when trying to enqueue a product to be synced to flow after'.
                    'being deleted. Product SKU: ' . $product->getSku(),
                    ['exception' => $e]
                );
            }
        }
    }
}
