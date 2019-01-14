<?php

namespace FlowCommerce\FlowConnector\Block\Adminhtml\SyncSku;

use Exception;
use FlowCommerce\FlowConnector\Model\SyncSku;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

/**
 * Class View
 * @package FlowCommerce\FlowConnector\Block\Adminhtml\SyncSku
 */
class View extends Template
{
    /**
     * Registry Key - Catalog Sync
     */
    const REGISTRY_KEY_SYNC_SKU = 'flowcommerce_flowconnector_sync_sku';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var SyncSku
     */
    private $syncSku;

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
     * Sync Sku getter
     * @return SyncSku
     */
    public function getSyncSku()
    {
        if ($this->syncSku === null) {
            $this->syncSku = $this->registry->registry(self::REGISTRY_KEY_SYNC_SKU);
        }
        return $this->syncSku;
    }

    /**
     * @param string $jsonString
     * @return string
     */
    public function beautifyJson($jsonString)
    {
        try {
            $return = '<pre>' . json_encode(json_decode($jsonString), JSON_PRETTY_PRINT) . '</pre>';
        } catch (Exception $e) {
            $return = $jsonString;
        }

        return $return;
    }
}
