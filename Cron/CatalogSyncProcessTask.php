<?php

namespace Flow\FlowConnector\Cron;

/**
 * Cron Task wrapper class to run catalog sync process.
 */
class CatalogSyncProcessTask {

    private $logger;
    private $util;
    private $catalogSync;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Flow\FlowConnector\Model\Util $util,
        \Flow\FlowConnector\Model\Sync\CatalogSync $catalogSync
    ) {
        $this->logger = $logger;
        $this->util = $util;
        $this->catalogSync = $catalogSync;
    }

    public function execute() {
        if ($this->util->isFlowEnabled()) {
            $this->logger->info("Running CatalogSyncProcessTask execute.");
            $this->catalogSync->process();
        } else {
            $this->logger->info("Skipping CatalogSyncProcessTask execute - connector disabled.");
        }
    }
}
