<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives order_upserted_v2 webhook events.
 * https://docs.flow.io/type/order-upserted-v-2
 */
class OrderUpsertedV2 extends Base {

    const EVENT_TYPE = 'order_upserted_v2';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
