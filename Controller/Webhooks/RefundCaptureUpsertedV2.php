<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives refund_capture_upserted_v2 webhook events.
 * https://docs.flow.io/type/refund-capture-upserted-v-2
 */
class RefundCaptureUpsertedV2 extends Base {

    const EVENT_TYPE = 'refund_capture_upserted_v2';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
