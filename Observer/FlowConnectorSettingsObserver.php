<?php

namespace FlowCommerce\FlowConnector\Observer;

use FlowCommerce\FlowConnector\Model\InventoryCenterManager;
use FlowCommerce\FlowConnector\Model\Sync\CatalogSync;
use FlowCommerce\FlowConnector\Model\Util as FlowUtil;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Observer class to update Flow webhooks on Flow Connector changes.
 */
class FlowConnectorSettingsObserver implements ObserverInterface
{
    /**
     * @var CatalogSync
     */
    private $catalogSync;

    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * @var FlowUtil
     */
    private $util;

    public function __construct(
        CatalogSync $catalogSync,
        FlowUtil $util,
        InventoryCenterManager $inventoryCenterManager,
        Logger $logger,
        MessageManager $messageManager,
        WebhookEventManager $webhookEventManager
    ) {
        $this->catalogSync = $catalogSync;
        $this->inventoryCenterManager = $inventoryCenterManager;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->util = $util;
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

        $this->logger->info('Updating Flow webhooks for store: ' . $storeId);

        $organizationId = $this->util->getFlowOrganizationId($storeId);
        $apiToken = $this->util->getFlowApiToken($storeId);
        $enabled = $this->util->isFlowEnabled($storeId);

        if ($enabled) {
            if ($organizationId != null && $apiToken != null) {
                if ($this->catalogSync->initFlowConnector($storeId)) {
                    $this->messageManager->addSuccess('Successfully initialized connector with Flow.');
                    $this->logger->info('Successfully initialized connector with Flow');
                } else {
                    $this->messageManager->addError('Error occurred initializing connector with Flow.');
                    $this->logger->info('Error occurred initializing connector with Flow.');
                }

                if ($this->webhookEventManager->registerWebhooks($storeId)) {
                    $this->messageManager->addSuccess('Successfully registered webhooks.');
                    $this->logger->info('Successfully registered webhooks.');
                } else {
                    $this->messageManager->addError('Error occurred registering webhooks.');
                    $this->logger->info('Error occurred registering webhooks.');
                }

                if ($this->inventoryCenterManager->fetchInventoryCenterKeys([$storeId])) {
                    $this->messageManager->addSuccess('Successfully fetched inventory center keys.');
                    $this->logger->info('Successfully fetched inventory center keys.');
                } else {
                    $this->messageManager->addError('Error occurred fetching inventory center keys.');
                    $this->logger->info('Error occurred fetching inventory center keys.');
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
