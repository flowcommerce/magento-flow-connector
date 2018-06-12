<?php

namespace Flow\FlowConnector\Cron;

/**
 * Cron Task wrapper class to run webhook event processing.
 */
class WebhookEventProcessTask {

    private $logger;
    private $util;
    private $webhookEventManager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Flow\FlowConnector\Model\Util $util,
        \Flow\FlowConnector\Model\WebhookEventManager $webhookEventManager
    ) {
        $this->logger = $logger;
        $this->util = $util;
        $this->webhookEventManager = $webhookEventManager;
    }

    public function execute() {
        if ($this->util->isFlowEnabled()) {
            $this->logger->info("Running WebhookEventProcessTask execute");
            $this->webhookEventManager->process();
        } else {
            $this->logger->info("Skipping WebhookEventProcessTask execute - connector disabled.");
        }
    }
}
