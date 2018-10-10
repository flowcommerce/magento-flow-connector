<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives order_deleted_v2 webhook events.
 * https://docs.flow.io/type/order-deleted-v-2
 */
class OrderDeletedV2 extends Base {

    const EVENT_TYPE = 'order_deleted_v2';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
