<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives allocation_deleted_v2 webhook events.
 * https://docs.flow.io/type/allocation-deleted-v-2
 */
class AllocationDeletedV2 extends Base {

    const EVENT_TYPE = 'allocation_deleted_v2';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
