<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives refund_upserted_v2 webhook events.
 * https://docs.flow.io/type/refund-upserted-v-2
 */
class RefundUpsertedV2 extends Base
{

    const EVENT_TYPE = 'refund_upserted_v2';

    public function getEventType()
    {
        return self::EVENT_TYPE;
    }
}
