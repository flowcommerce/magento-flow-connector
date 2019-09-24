<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives order_placed webhook events.
 * https://docs.flow.io/type/order-placed
 */
class OrderPlaced extends Base
{

    const EVENT_TYPE = 'order_placed';

    public function getEventType()
    {
        return self::EVENT_TYPE;
    }
}

