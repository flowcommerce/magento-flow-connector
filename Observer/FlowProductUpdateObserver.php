<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer class to queue product changes.
 * @package FlowCommerce\FlowConnector\Observer
 */
class FlowProductUpdateObserver implements ObserverInterface
{

    private $catalogSync;

    public function __construct(
        \FlowCommerce\FlowConnector\Model\Sync\CatalogSync $catalogSync
    ) {
        $this->catalogSync = $catalogSync;
    }

    /**
     * This observer triggers after a catalog product save and queues the sku
     * for syncing to Flow.
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getProduct();
        $this->catalogSync->queue($product);
    }
}
