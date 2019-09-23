<?php

namespace FlowCommerce\FlowConnector\Cron;

use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Api\InventorySyncManagementInterface as InventorySyncManager;
use \FlowCommerce\FlowConnector\Model\Api\Auth;

/**
 * Cron Task wrapper class to run inventory sync queue all.
 * @package FlowCommerce\FlowConnector\Cron
 */
class InventorySyncQueueAllTask
{
    /**
     * @var InventorySyncManager
     */
    private $inventorySyncManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * CatalogSyncQueueAllTask constructor.
     * @param Logger $logger
     * @param InventorySyncManager $inventorySyncManager
     */
    public function __construct(
        Logger $logger,
        InventorySyncManager $inventorySyncManager,
        Auth $auth
    ) {
        $this->logger = $logger;
        $this->inventorySyncManager = $inventorySyncManager;
        $this->auth = $auth;
    }

    /**
     * Defers queueing of all stock items to the inventory sync model
     * @return void
     */
    public function execute()
    {
        if ($this->auth->isFlowProductionOrganization()) {
            $this->logger->info('Running InventorySyncQueueAllTask execute.');
            $this->inventorySyncManager->enqueueAllStockItems();
        } else {
            $this->logger->info('Skipping InventorySyncQueueAllTask due to sandbox config');
        }
    }
}

