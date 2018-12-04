<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\IntegrationManagementInterface;
use FlowCommerce\FlowConnector\Api\InventoryCenterManagementInterface as InventoryCenterManager;
use FlowCommerce\FlowConnector\Api\SyncSkuPriceAttributesManagementInterface as SyncSkuPriceAttributesManager;
use FlowCommerce\FlowConnector\Api\WebhookManagementInterface as WebhookManager;

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
     * IntegrationManager constructor.
     * @param InventoryCenterManager $inventoryCenterManager
     * @param SyncSkuPriceAttributesManager $syncSkuPriceAttributesManager
     * @param WebhookManager $webhookManager
     */
    public function __construct(
        InventoryCenterManager $inventoryCenterManager,
        SyncSkuPriceAttributesManager $syncSkuPriceAttributesManager,
        WebhookManager $webhookManager
    ) {
        $this->inventoryCenterManager = $inventoryCenterManager;
        $this->syncSkuPriceAttributesManager = $syncSkuPriceAttributesManager;
        $this->webhookManager = $webhookManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeIntegrationForStoreView($storeId)
    {
        $resultInventoryCenterFetchKeys = $this->inventoryCenterManager->fetchInventoryCenterKeys([$storeId]);
        $resultSyncSkuPriceAttributes = $this->syncSkuPriceAttributesManager->createPriceAttributesInFlow($storeId);
        $resultWebhookRegistration = $this->webhookManager->registerAllWebhooks($storeId);
        return $resultInventoryCenterFetchKeys && $resultSyncSkuPriceAttributes && $resultWebhookRegistration;
    }
}
