<?php

namespace FlowCommerce\FlowConnector\Model\Config\Source;

/**
 * Class ShipmentEvent
 * @package FlowCommerce\FlowConnector\Model\Config\Source
 */
class ShipmentEvent implements \Magento\Framework\Option\ArrayInterface
{
    const VALUE_NEVER = 0;
    const VALUE_WHEN_SHIPPED = 1;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::VALUE_NEVER, 'label' => __('Never')],
            ['value' => self::VALUE_WHEN_SHIPPED, 'label' => __('When Shipped in Flow')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::VALUE_NEVER => __('Never'),
            self::VALUE_WHEN_SHIPPED => __('When Shipped in Flow')
        ];
    }
}
