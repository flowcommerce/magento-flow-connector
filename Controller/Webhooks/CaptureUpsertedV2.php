<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives capture_upserted_v2 webhook events.
 * https://docs.flow.io/type/capture-upserted-v-2
 */
class CaptureUpsertedV2 extends Base
{

    const EVENT_TYPE = 'capture_upserted_v2';

    public function getEventType()
    {
        return self::EVENT_TYPE;
    }
}
