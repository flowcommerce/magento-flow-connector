<?php

namespace Flow\FlowConnector\Controller\Webhooks;

/**
 * Receives online_authorization_upserted_v2 webhook events.
 * https://docs.flow.io/type/online-authorization-upserted-v-2
 */
class OnlineAuthorizationUpsertedV2 extends Base {

    const EVENT_TYPE = 'online_authorization_upserted_v2';

    public function getEventType() {
        return self::EVENT_TYPE;
    }
}
