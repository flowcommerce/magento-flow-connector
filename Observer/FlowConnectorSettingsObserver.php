<?php

namespace FlowCommerce\FlowConnector\Observer;

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
        \FlowCommerce\FlowConnector\Model\Util $util,
        \FlowCommerce\FlowConnector\Model\WebhookEventManager $webhookEventManager,
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
        $storeId = $observer->getStore(); // string of store ID

        $this->logger->info('Updating Flow webhooks for store: ' . $storeId);

        $organizationId = $this->util->getFlowOrganizationId($storeId);
        $apiToken = $this->util->getFlowApiToken($storeId);
        $enabled = $this->util->isFlowEnabled($storeId);

        if ($enabled) {
            if ($organizationId != null && $apiToken != null) {
                if ($this->webhookEventManager->registerWebhooks($storeId)) {
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
