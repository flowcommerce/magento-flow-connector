<?php

namespace FlowCommerce\FlowConnector\Cron;

use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Model\Configuration;
use \FlowCommerce\FlowConnector\Api\InventorySyncManagementInterface as InventorySyncManager;

/**
 * Cron Task wrapper class to run inventory sync queue all.
 * @package FlowCommerce\FlowConnector\Cron
 */
class InventorySyncQueueAllTask
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var InventorySyncManager
     */
    private $inventorySyncManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CatalogSyncQueueAllTask constructor.
     * @param Logger $logger
     * @param InventorySyncManager $inventorySyncManager
     */
    public function __construct(
        Logger $logger,
        InventorySyncManager $inventorySyncManager,
        Configuration $configuration
    ) {
        $this->logger = $logger;
        $this->inventorySyncManager = $inventorySyncManager;
        $this->configuration = $configuration;
    }

    /**
     * Defers queueing of all stock items to the inventory sync model
     * @return void
     */
    public function execute()
    {
        if ($this->configuration->isDailyCatalogSyncEnabled()) {
            $this->logger->info('Running InventorySyncQueueAllTask execute.');
            $this->inventorySyncManager->enqueueAllStockItems();
        } else {
            $this->logger->info('Skipping InventorySyncQueueAllTask due to sandbox config');
        }
    }
}
