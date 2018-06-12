<?php

namespace Flow\FlowConnector\Controller\Webhooks;

/**
 * Receives order_upserted webhook events.
 * https://docs.flow.io/type/order-upserted
 */
class OrderUpserted extends Base {

    const EVENT_TYPE = 'order_upserted';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
