<?php

namespace FlowCommerce\FlowConnector\Block\Adminhtml\SyncOrder;

use Exception;
use FlowCommerce\FlowConnector\Model\SyncOrder;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

/**
 * Class View
 * @package FlowCommerce\FlowConnector\Block\Adminhtml\SyncOrder
 */
class View extends Template
{
    /**
     * Registry Key - Catalog Sync
     */
    const REGISTRY_KEY_SYNC_ORDER = 'flowcommerce_flowconnector_sync_order';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var SyncOrder
     */
    private $syncOrder;

    /**
     * View constructor.
     * @param Template\Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * Sync Order getter
     * @return SyncOrder
     */
    public function getSyncOrder()
    {
        if ($this->syncOrder === null) {
            $this->syncOrder = $this->registry->registry(self::REGISTRY_KEY_SYNC_ORDER);
        }
        return $this->syncOrder;
    }
}
