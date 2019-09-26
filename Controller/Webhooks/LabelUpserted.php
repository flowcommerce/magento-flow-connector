<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

/**
 * Receives label_upserted webhook events.
 * https://docs.flow.io/type/label-upserted
 */
class LabelUpserted extends Base
{

    const EVENT_TYPE = 'label_upserted';

    public function getEventType()
    {
        return self::EVENT_TYPE;
    }
}
