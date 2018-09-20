<?php

namespace FlowCommerce\FlowConnector\Cron;

use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Model\SyncSkuManager;

/**
 * Cron Task wrapper class to run catalog sync queue all.
 * @package FlowCommerce\FlowConnector\Cron
 */
class CatalogSyncQueueAllTask
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
     * CatalogSyncQueueAllTask constructor.
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
     * Defers queueing of all products to the catalog sync model
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Running CatalogSyncQueueAllTask execute.');
        $this->syncSkuManager->enqueueAllProducts();
    }
}
