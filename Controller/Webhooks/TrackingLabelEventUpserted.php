<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives tracking_label_event_upserted webhook events.
 * https://docs.flow.io/type/tracking-label-event-upserted
 */
class TrackingLabelEventUpserted extends Base
{

    const EVENT_TYPE = 'tracking_label_event_upserted';

    public function getEventType()
    {
        return self::EVENT_TYPE;
    }
}

