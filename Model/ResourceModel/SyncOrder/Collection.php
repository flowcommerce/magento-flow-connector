<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel\SyncOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use FlowCommerce\FlowConnector\Model\SyncOrder;
use FlowCommerce\FlowConnector\Model\ResourceModel;

class Collection extends AbstractCollection
{

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'flow_connector_sync_orders';
    protected $_eventObject = 'sync_order';

    protected function _construct()
    {
        $this->_init(
            SyncOrder::class,
            ResourceModel\SyncOrder::class
        );
    }
}
