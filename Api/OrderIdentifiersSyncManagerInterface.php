<?php

namespace FlowCommerce\FlowConnector\Api;

use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @package FlowCommerce\FlowConnector\Api
 */
interface OrderIdentifiersSyncManagerInterface
{
    /**
     * Queues Magento and Flow order IDs for sycn with Flow API.
     *
     * @param int $storeId
     * @param array $magentoFlowOrderIds
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function queueOrderIdentifiersforSync(int $storeId, array $magentoFlowOrderIds): void;

    /**
     * Syncs Magento and Flow order IDs using POST request towards Flow API.
     *
     * @param int $storeId
     * @param array $magentoFlowOrderIds
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function syncOrderIdentifiers(int $storeId, array $magentoFlowOrderIds): void;
}
