<?php

namespace FlowCommerce\FlowConnector\Observer;

use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;

/**
 * Observer class to update Flow webhooks on Flow Connector changes.
 */
class FlowConnectorSettingsObserver implements ObserverInterface
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * FlowConnectorSettingsObserver constructor.
     * @param MessageManager $messageManager
     * @param WebhookEventManager $webhookEventManager
     */
    public function __construct(
        MessageManager $messageManager,
        WebhookEventManager $webhookEventManager
    ) {
        $this->messageManager = $messageManager;
        $this->webhookEventManager = $webhookEventManager;
    }

    /**
     * This observer triggers after Flow connector settings are updated in the
     * Admin Store Configuration.
     * @param Observer $observer
     * @return void
     * @throws
     */
    public function execute(Observer $observer)
    {
        $storeId = $observer->getStore(); // string of store ID

        try {
            $this->webhookEventManager->registerWebhooks($storeId);
            $this->messageManager->addSuccess('Successfully initialized Flow configuration.');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(sprintf(
                'An error occurred while initializing Flow configuration: %s.',
                $e->getMessage()
            ));
        }
    }
}
