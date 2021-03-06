<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Order extends AbstractDb
{

    public function __construct(Context $ctx)
    {
        parent::__construct($ctx);
    }

    protected function _construct()
    {
        $this->_init('flow_connector_orders', 'id');
    }

    protected function _getDefaultAttributes()
    {
        return [
            'created_at',
            'updated_at',
        ];
    }
}

