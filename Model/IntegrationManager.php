<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\IntegrationManagementInterface;
use FlowCommerce\FlowConnector\Api\InventoryCenterManagementInterface as InventoryCenterManager;
use FlowCommerce\FlowConnector\Api\SyncSkuPriceAttributesManagementInterface as SyncSkuPriceAttributesManager;
use FlowCommerce\FlowConnector\Api\WebhookManagementInterface as WebhookManager;
use FlowCommerce\FlowConnector\Api\SyncManagementInterface as SyncManager;

/**
 * Class IntegrationManager
 * @package FlowCommerce\FlowConnector\Model
 */
class IntegrationManager implements IntegrationManagementInterface
{
    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * @var SyncSkuPriceAttributesManager
     */
    private $syncSkuPriceAttributesManager;

    /**
     * @var WebhookManager
     */
    private $webhookManager;

    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * IntegrationManager constructor.
     * @param InventoryCenterManager $inventoryCenterManager
     * @param SyncSkuPriceAttributesManager $syncSkuPriceAttributesManager
     * @param WebhookManager $webhookManager
     * @param SyncManager $syncManager
     */
    public function __construct(
        InventoryCenterManager $inventoryCenterManager,
        SyncSkuPriceAttributesManager $syncSkuPriceAttributesManager,
        WebhookManager $webhookManager,
        SyncManager $syncManager
    ) {
        $this->inventoryCenterManager = $inventoryCenterManager;
        $this->syncSkuPriceAttributesManager = $syncSkuPriceAttributesManager;
        $this->webhookManager = $webhookManager;
        $this->syncManager = $syncManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeIntegrationForStoreView($storeId)
    {
        $resultInventoryCenterFetchKeys = $this->inventoryCenterManager->fetchInventoryCenterKeys([$storeId]);
        $resultSyncSkuPriceAttributes = $this->syncSkuPriceAttributesManager->createPriceAttributesInFlow($storeId);
        $resultWebhookRegistration = $this->webhookManager->registerAllWebhooks($storeId);
        $resultWebhookSettings = $this->webhookManager->updateWebhookSettings($storeId);
        $resultSyncStreamRegistration = $this->syncManager->registerAllSyncStreams($storeId);
        return $resultInventoryCenterFetchKeys && $resultSyncSkuPriceAttributes
            && $resultWebhookRegistration && $resultWebhookSettings;
    }
}
