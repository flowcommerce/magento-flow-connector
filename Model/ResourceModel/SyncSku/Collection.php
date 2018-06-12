<?php

namespace Flow\FlowConnector\Model\ResourceModel\SyncSku;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Flow\FlowConnector\Model\{
    SyncSku,
    ResourceModel
};

class Collection extends AbstractCollection {

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'flow_connector_sync_skus';
    protected $_eventObject = 'sync_sku';

    protected function _construct() {
        $this->_init(
            SyncSku::class,
            ResourceModel\SyncSku::class
        );
    }
}
