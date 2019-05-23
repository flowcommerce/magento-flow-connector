<?php

namespace FlowCommerce\FlowConnector\Api;

use Psr\Log\LoggerInterface as Logger;

/**
 * Interface Webhook Management Interface
 * @package FlowCommerce\FlowConnector\Api
 */
interface WebhookManagementInterface
{
    /**
     * Returns a list of webhooks registered with Flow
     * @param $storeId
     * @return string[]
     */
    public function getRegisteredWebhooks($storeId);

    /**
     * Registers webhooks with Flow
     * @param int $storeId - ID of store
     * @return bool
     * @throws \Exception
     */
    public function registerAllWebhooks($storeId);

    /**
     * Registers a webhook with Flow
     * @param $storeId
     * @param $endpointStub
     * @param $events
     */
    public function registerWebhook($storeId, $endpointStub, $events);

    /**
     * Update webhook settings
     * @param $storeId
     */
    public function updateWebhookSettings($storeId);

    /**
     * Sets Logger
     * @param Logger $logger
     */
    public function setLogger(Logger $logger);
}
