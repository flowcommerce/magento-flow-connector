<?php

namespace FlowCommerce\FlowConnector\Api;

/**
 * Interface SyncSkuAttributeManagementInterface
 * @package FlowCommerce\FlowConnector\Api
 */
interface SyncSkuPriceAttributesManagementInterface
{
    /**
     * Creates price attributes in Flow.io
     * @param int $storeId
     * @return bool
     */
    public function createPriceAttributesInFlow($storeId);
}
