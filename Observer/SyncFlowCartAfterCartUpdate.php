<?php

namespace FlowCommerce\FlowConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\FlowCartManager;
use FlowCommerce\FlowConnector\Model\Configuration;

/**
 * Class SyncFlowCartAfterCartUpdate
 * @package FlowCommerce\FlowConnector\Observer
 */
class SyncFlowCartAfterCartUpdate implements ObserverInterface
{

    /** @var FlowCartManager */
    private $flowCartManager;

    /** @var Logger */
    private $logger;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * SyncFlowCartAfterCartUpdate constructor.
     * @param FlowCartManager $flowCartManager
     * @param Logger $logger
     * @param Configuration $configuration
     * @return void
     */
    public function __construct(
        FlowCartManager $flowCartManager,
        Logger $logger,
        Configuration $configuration
    ) {
        $this->flowCartManager = $flowCartManager;
        $this->logger = $logger;
        $this->configuration = $configuration;
    }

    /**
     * Sync cart
     * @param Observer $observer
     * @SuppressWarnings("unused")
     */
    public function execute(Observer $observer)
    {
        if (!$this->configuration->isFlowEnabled()) {
            return;
        }

        try {
            $this->flowCartManager->syncCartData();
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Unable to sync Magento and Flow carts due to %s', $e->getMessage()));
        }
    }
}
