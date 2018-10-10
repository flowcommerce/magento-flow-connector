<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Catalog\Model\Product;

use FlowCommerce\FlowConnector\Model\InventorySyncManager;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Action
 * @package FlowCommerce\FlowConnector\Plugin\Magento\Catalog\Model\Product
 */
class Action
{
    /**
     * @var InventorySyncManager
     */
    private $inventorySyncManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * Action constructor.
     * @param InventorySyncManager $inventorySyncManager
     * @param Logger $logger
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        InventorySyncManager $inventorySyncManager,
        Logger $logger,
        SyncSkuManager $syncSkuManager
    ) {
        $this->inventorySyncManager = $inventorySyncManager;
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * After a mass attribute update, we need to schedule updated products to flow
     * @param ProductAction $productAction
     * @param mixed $result
     * @return mixed $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateAttributes(ProductAction $productAction, $result)
    {
        $productIds = $productAction->getData('product_ids');
        if (count($productIds)) {
            $this->logger->info(
                'Flow Catalog Sync - After mass update processing, deferring to product and inventory sync managers',
                ['product_ids' => $productIds]
            );
            $this->syncSkuManager->enqueueMultipleProductsByProductIds($productIds);
            $this->inventorySyncManager->enqueueMultipleStockItemByProductIds($productIds);
        }
        return $result;
    }
}
