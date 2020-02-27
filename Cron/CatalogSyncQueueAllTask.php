<?php

namespace FlowCommerce\FlowConnector\Cron;

use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Model\Configuration;
use \FlowCommerce\FlowConnector\Model\SyncSkuManager;

/**
 * Cron Task wrapper class to run catalog sync queue all.
 * @package FlowCommerce\FlowConnector\Cron
 */
class CatalogSyncQueueAllTask
{
    /**
     * @var Configuration
     */
    private $configuration;

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
        SyncSkuManager $syncSkuManager,
        Configuration $configuration
    ) {
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
        $this->configuration = $configuration;
    }

    /**
     * Defers queueing of all products to the catalog sync model
     * @return void
     */
    public function execute()
    {
        if ($this->configuration->isDailyCatalogSyncEnabled()) {
            $this->logger->info('Running CatalogSyncQueueAllTask execute.');
            $this->syncSkuManager->enqueueAllProducts();
        } else {
            $this->logger->info('Skipping CatalogSyncQueueAllTask due to sandbox config');
        }
    }
}
