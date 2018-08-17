<?php

namespace FlowCommerce\FlowConnector\Cron;

/**
 * Cron Task wrapper class to run catalog sync process.
 */
class CatalogSyncProcessTask {

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
        $this->logger->info("Running CatalogSyncProcessTask execute.");
        $this->catalogSync->process();
    }
}
