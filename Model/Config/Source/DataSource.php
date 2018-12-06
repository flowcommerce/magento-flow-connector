<?php

namespace FlowCommerce\FlowConnector\Model\Config\Source;

/**
 * Class DataSource
 * @package FlowCommerce\FlowConnector\Model\Config\Source
 */
class DataSource implements \Magento\Framework\Option\ArrayInterface
{
    const VALUE_MAGENTO = 1;
    const VALUE_FLOW = 2;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::VALUE_MAGENTO, 'label' => __('Magento')],
            ['value' => self::VALUE_FLOW, 'label' => __('Flow')]
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
            self::VALUE_MAGENTO => __('Magento'),
            self::VALUE_MAGENTO => __('Flow')
        ];
    }
}
