<?php

namespace FlowCommerce\FlowConnector\Cron;

/**
 * Cron Task wrapper class to run webhook event processing.
 */
class WebhookEventProcessTask {

    private $logger;
    private $util;
    private $webhookEventManager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \FlowCommerce\FlowConnector\Model\Util $util,
        \FlowCommerce\FlowConnector\Model\WebhookEventManager $webhookEventManager
    ) {
        $this->logger = $logger;
        $this->util = $util;
        $this->webhookEventManager = $webhookEventManager;
    }

    public function execute() {
        $this->logger->info("Running WebhookEventProcessTask execute");
        $this->webhookEventManager->process();
    }
}
