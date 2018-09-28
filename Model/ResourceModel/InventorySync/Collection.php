<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel\InventorySync;

use FlowCommerce\FlowConnector\Model\ResourceModel\InventorySync as InventorySyncResourceModel;
use FlowCommerce\FlowConnector\Model\InventorySync as InventorySyncModel;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package FlowCommerce\FlowConnector\Model\ResourceModel\InventorySync
 */
class Collection extends AbstractCollection
{
    /**
     * Id Field Name
     * @var string
     */
    protected $_idFieldName = InventorySyncInterface::DATA_KEY_ID;

    /**
     * Event Prefix
     * @var string
     */
    protected $_eventPrefix = 'flow_connector_inventory_sync';

    /**
     * Event Object
     * @var string
     */
    protected $_eventObject = 'inventory_sync';

    /**
     * Initializes collection
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            InventorySyncModel::class,
            InventorySyncResourceModel::class
        );
    }
}
