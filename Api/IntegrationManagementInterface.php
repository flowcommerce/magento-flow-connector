<?php

namespace FlowCommerce\FlowConnector\Api;

/**
 * Interface IntegrationManagementInterface
 * @package FlowCommerce\FlowConnector\Api
 */
interface IntegrationManagementInterface
{
    /**
     * Given a store id, configures it's integration with Flow.io.
     * This method is a wrapper for:
     * - Inventory Center fetch keys method
     * - Catalog Sync attribute save method
     * - Webhook Event registration
     * @param $storeId
     * @return bool
     */
    public function initializeIntegrationForStoreView($storeId);
}
