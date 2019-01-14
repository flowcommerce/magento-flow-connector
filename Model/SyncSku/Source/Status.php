<?php

namespace FlowCommerce\FlowConnector\Model\SyncSku\Source;

use FlowCommerce\FlowConnector\Model\SyncSku;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status
 * @package FlowCommerce\FlowConnector\Model\SyncSku\Source
 */
class Status implements OptionSourceInterface
{
    /**
     * @var SyncSku
     */
    protected $syncSku;

    /**
     * Constructor
     * @param SyncSku $syncSku
     */
    public function __construct(SyncSku $syncSku)
    {
        $this->syncSku = $syncSku;
    }

    /**
     * Get options
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->syncSku->getAvailableStatuses();
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
