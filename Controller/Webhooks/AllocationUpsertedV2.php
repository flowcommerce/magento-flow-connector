<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives allocation_upserted_v2 webhook events.
 * https://docs.flow.io/type/allocation-upserted-v-2
 */
class AllocationUpsertedV2 extends Base {

    const EVENT_TYPE = 'allocation_upserted_v2';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
