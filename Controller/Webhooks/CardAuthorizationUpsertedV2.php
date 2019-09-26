<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives card_authorization_upserted_v2 webhook events.
 * https://docs.flow.io/type/card-authorization-upserted-v-2
 */
class CardAuthorizationUpsertedV2 extends Base
{

    const EVENT_TYPE = 'card_authorization_upserted_v2';

    public function getEventType()
    {
        return self::EVENT_TYPE;
    }
}
