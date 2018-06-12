<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use FlowCommerce\FlowConnector\Model\{
    WebhookEvent,
    ResourceModel
};

class Collection extends AbstractCollection {

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'flow_connector_webhook_events';
    protected $_eventObject = 'webhook_event';

    protected function _construct() {
        $this->_init(
            WebhookEvent::class,
            ResourceModel\WebhookEvent::class
        );
    }
}
