<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use FlowCommerce\FlowConnector\Model\SyncSku;
use FlowCommerce\FlowConnector\Model\ResourceModel;

class Collection extends AbstractCollection
{

    protected $_idFieldName = 'value';

    protected function _construct()
    {
        $this->_init(
            SyncSku::class,
            ResourceModel\SyncSku::class
        );
    }
}
