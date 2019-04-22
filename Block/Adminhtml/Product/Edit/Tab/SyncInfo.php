<?php

namespace FlowCommerce\FlowConnector\Block\Adminhtml\Product\Edit\Tab;

use Exception;
use FlowCommerce\FlowConnector\Block\Adminhtml\SyncSku\View as SyncSkuView;
use FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\CollectionFactory as SyncSkuCollectionFactory;
use FlowCommerce\FlowConnector\Model\SyncSku;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

/**
 * Class SyncInfo
 * @package FlowCommerce\FlowConnector\Block\Adminhtml\Product\Edit\Tab
 */
class SyncInfo extends Template
{
    /**
     * Template
     * @var string
     */
    protected $_template = 'FlowCommerce_FlowConnector::product/sync_info.phtml';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var SyncSkuCollectionFactory
     */
    private $syncSkuCollectionFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        SyncSkuCollectionFactory $syncSkuCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->syncSkuCollectionFactory = $syncSkuCollectionFactory;
    }

    /**
     * @return ProductInterface
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getSyncInfoHtml()
    {
        $return = '';

        if ($product = $this->getProduct()) {
            $collection = $this->syncSkuCollectionFactory->create();
            $collection->addFieldToFilter(SyncSku::DATA_KEY_SKU, ['eq' => $product->getSku()]);

            if ($collection->count() > 0) {
                $syncSku = $collection->getFirstItem();
                $this->registry->register(SyncSkuView::REGISTRY_KEY_SYNC_SKU, $syncSku);

                /** @var SyncSkuView $syncSkuViewBlock */
                $syncSkuViewBlock = $this->getLayout()
                    ->createBlock(SyncSkuView::class, 'syncSkuInfo');
                $syncSkuViewBlock->setTemplate('FlowCommerce_FlowConnector::syncsku/view.phtml');

                $return = $syncSkuViewBlock->toHtml();
            }
        }

        return $return;
    }
}
