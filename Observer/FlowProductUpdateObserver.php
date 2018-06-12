<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\Framework\Event\{
    ObserverInterface,
    Observer
};

/**
 * Observer class to queue product changes.
 */
class FlowProductUpdateObserver implements ObserverInterface {

    private $logger;
    private $jsonHelper;
    private $catalogSync;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \FlowCommerce\FlowConnector\Model\Sync\CatalogSync $catalogSync
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->catalogSync = $catalogSync;
    }

    /**
    * This observer triggers after a catalog product save and queues the sku
    * for syncing to Flow.
    */
    public function execute(Observer $observer) {
        $product = $observer->getProduct();
        $this->catalogSync->queue($product);
    }
}
