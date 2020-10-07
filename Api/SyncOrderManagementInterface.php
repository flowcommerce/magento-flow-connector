<?php

namespace FlowCommerce\FlowConnector\Api;

/**
 * Interface SyncOrder Management Interface
 * @package FlowCommerce\FlowConnector\Api
 */
interface SyncOrderManagementInterface
{
    /**
     * Sync Order processing
     * @return boolean
     */
    public function process();

    /**
     * Marks Sync Order as processing
     * @param string $orderNumber
     * @param int $storeId
     * @return void
     */
    public function syncByValue(string $orderNumber, int $storeId);
}
