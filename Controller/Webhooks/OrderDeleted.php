<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives order_deleted webhook events.
 * https://docs.flow.io/type/order-deleted
 */
class OrderDeleted extends Base {

    const EVENT_TYPE = 'order_deleted';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
