<?php

namespace FlowCommerce\FlowConnector\Model\WebhookEvent\Source;

use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status
 * @package FlowCommerce\FlowConnector\Model\WebhookEvent\Source
 */
class Status implements OptionSourceInterface
{
    /**
     * @var WebhookEvent
     */
    protected $webhookEvent;

    /**
     * Constructor
     * @param WebhookEvent $webhookEvent
     */
    public function __construct(WebhookEvent $webhookEvent)
    {
        $this->webhookEvent = $webhookEvent;
    }

    /**
     * Get options
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->webhookEvent->getAvailableStatuses();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
