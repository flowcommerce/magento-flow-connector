<?php

namespace Flow\FlowConnector\Model\ResourceModel\WebhookItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Coedition\ConnectorCommon\Model\{
    LocalItem,
    ResourceModel
};


class Collection extends AbstractCollection {

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'flow_connector_local_items';
    protected $_eventObject = 'local_items';

    protected function _construct() {
        $this->_init(
            LocalItem::class,
            ResourceModel\LocalItem::class
        );
    }
}
