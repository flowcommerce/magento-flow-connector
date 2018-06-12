<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives local_item_upserted webhook events.
 * https://docs.flow.io/type/local-item-deleted
 */
class LocalItemUpserted extends Base {

    const EVENT_TYPE = 'local_item_upserted';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
