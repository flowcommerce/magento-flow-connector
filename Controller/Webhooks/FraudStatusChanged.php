<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives fraud_status_changed webhook events.
 * https://docs.flow.io/type/fraud-status-changed
 */
class FraudStatusChanged extends Base
{

    const EVENT_TYPE = 'fraud_status_changed';

    public function getEventType()
    {
        return self::EVENT_TYPE;
    }
}
