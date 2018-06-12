<?php

namespace Flow\FlowConnector\Observer;

use Magento\Framework\Event\{
    ObserverInterface,
    Observer
};

/**
 * Observer class to update Flow webhooks on Flow Connector changes.
 */
class FlowConnectorSettingsObserver implements ObserverInterface {

    private $logger;
    private $util;
    private $webhookEventManager;
    private $messageManager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Flow\FlowConnector\Model\Util $util,
        \Flow\FlowConnector\Model\WebhookEventManager $webhookEventManager,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->logger = $logger;
        $this->util = $util;
        $this->webhookEventManager = $webhookEventManager;
        $this->messageManager = $messageManager;
    }

    /**
    * This observer triggers after Flow connector settings are updated in the
    * Admin Store Configuration.
    */
    public function execute(Observer $observer) {
        $this->logger->info('Updating Flow webhooks');

        $organizationId = $this->util->getFlowOrganizationId();
        $apiToken = $this->util->getFlowApiToken();
        $enabled = $this->util->isFlowEnabled();

        if ($enabled) {
            if ($organizationId != null && $apiToken != null) {
                if ($this->webhookEventManager->registerWebhooks()) {
                    $this->messageManager->addSuccess('Successfully registered webhooks.');
                    $this->logger->info('Successfully registered webhooks');
                } else {
                    $this->messageManager->addError('Error occurred registering webhooks.');
                    $this->logger->info('Error occurred registering webhooks.');
                }
            } else {
                $this->messageManager->addError('Credentials are incomplete.');
                $this->logger->info('Credentials are incomplete.');
            }
        } else {
            $this->logger->info('Flow connector disabled.');
        }
    }
}
