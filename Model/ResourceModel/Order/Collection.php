<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel\Order;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use FlowCommerce\FlowConnector\Model\Order;
use FlowCommerce\FlowConnector\Model\ResourceModel;

class Collection extends AbstractCollection
{

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'flow_connector_orders';
    protected $_eventObject = 'orders';

    protected function _construct()
    {
        $this->_init(
            Order::class,
            ResourceModel\Order::class
        );
    }
}

