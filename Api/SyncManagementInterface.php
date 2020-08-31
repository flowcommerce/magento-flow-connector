<?php

namespace FlowCommerce\FlowConnector\Api;

/**
 * Interface Sync Management Interface
 * @package FlowCommerce\FlowConnector\Api
 */
interface SyncManagementInterface
{
    /**
     * Registers all sync streams to Flow
     * @param $storeId
     * @param $type
     */
    public function registerAllSyncStreams($storeId);

    /**
     * Puts a sync stream to Flow
     * @param $storeId
     * @param $type
     */
    public function putSyncStream($storeId, $type);

    /**
     * Puts a sync stream record to Flow
     * @param $storeId
     * @param $type
     * @param $value
     */
    public function putSyncStreamRecord($storeId, $type, $value);
}

