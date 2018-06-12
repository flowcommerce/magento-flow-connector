<?php

namespace FlowCommerce\FlowConnector\Cron;

/**
 * Cron Task wrapper class to run catalog sync queue all.
 */
class CatalogSyncQueueAllTask {

    private $logger;
    private $util;
    private $catalogSync;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \FlowCommerce\FlowConnector\Model\Util $util,
        \FlowCommerce\FlowConnector\Model\Sync\CatalogSync $catalogSync
    ) {
        $this->logger = $logger;
        $this->util = $util;
        $this->catalogSync = $catalogSync;
    }

    public function execute() {
        if ($this->util->isFlowEnabled()) {
            $this->logger->info("Running CatalogSyncQueueAllTask execute.");
            $this->catalogSync->queueAll();
        } else {
            $this->logger->info("Skipping CatalogSyncQueueAllTask execute - connector disabled.");
        }
    }
}
