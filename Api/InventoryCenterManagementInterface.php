<?php

namespace FlowCommerce\FlowConnector\Api;

/**
 * Interface InventoryCenterManagementInterface
 * @package FlowCommerce\FlowConnector\Api
 */
interface InventoryCenterManagementInterface
{
    /**
     * Fetches inventory center keys from Flow's api and stores them in magento's config
     * @param int[]|null $storeIds
     * @return bool
     */
    public function fetchInventoryCenterKeys($storeIds = []);

    /**
     * Given a store id, returns the configured default center key
     * @param $storeId
     * @return string
     */
    public function getDefaultCenterKeyForStore($storeId);
}
