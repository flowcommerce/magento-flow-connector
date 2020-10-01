<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SyncOrder
 * @package FlowCommerce\FlowConnector\Model\ResourceModel
 */
class SyncOrder extends AbstractDb
{
    /**
     * Initializes resource model
     */
    protected function _construct()
    {
        $this->_init('flow_connector_sync_orders', 'value');
    }

    /**
     * Returns default attributes
     * @return string[]
     */
    protected function _getDefaultAttributes()
    {
        return [
            'created_at',
            'updated_at',
        ];
    }
}
