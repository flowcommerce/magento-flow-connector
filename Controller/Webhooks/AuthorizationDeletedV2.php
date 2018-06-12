<?php

namespace Flow\FlowConnector\Controller\Webhooks;

/**
 * Receives authorization_deleted_v2 webhook events.
 * https://docs.flow.io/type/authorization-deleted-v-2
 */
class AuthorizationDeletedV2 extends Base {

    const EVENT_TYPE = 'authorization_deleted_v2';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
