<?php

namespace Flow\FlowConnector\Controller\Webhooks;

/**
 * Receives local_item_deleted webhook events.
 * https://docs.flow.io/type/local-item-deleted
 */
class LocalItemDeleted extends Base {

    const EVENT_TYPE = 'local_item_deleted';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
