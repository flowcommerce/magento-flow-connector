<?php

namespace FlowCommerce\FlowConnector\Cron;

use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Model\SyncSkuManager;
use \FlowCommerce\FlowConnector\Model\Api\Auth;

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
     * @var Auth
     */
    private $auth;

    /**
     * CatalogSyncQueueAllTask constructor.
     * @param Logger $logger
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        Logger $logger,
        SyncSkuManager $syncSkuManager,
        Auth $auth
    ) {
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
        $this->auth = $auth;
    }

    /**
     * Defers queueing of all products to the catalog sync model
     * @return void
     */
    public function execute()
    {
        if ($this->auth->isFlowProductionOrganization()) {
            $this->logger->info('Running CatalogSyncQueueAllTask execute.');
            $this->syncSkuManager->enqueueAllProducts();
        } else {
            $this->logger->info('Skipping CatalogSyncQueueAllTask due to sandbox config');
        }
    }
}

