<?php

namespace FlowCommerce\FlowConnector\Observer;

use FlowCommerce\FlowConnector\Model\InventorySyncManager;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\CatalogImportExport\Model\Import\Product as ProductImport;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class SyncProductsAfterImportDelete
 * @package FlowCommerce\FlowConnector\Observer
 */
class SyncProductsAfterImportDelete implements ObserverInterface
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
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * Action constructor.
     * @param InventorySyncManager $inventorySyncManager
     * @param Logger $logger
     * @param ProductRepository $productRepository
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        InventorySyncManager $inventorySyncManager,
        Logger $logger,
        ProductRepository $productRepository,
        SyncSkuManager $syncSkuManager
    ) {
        $this->inventorySyncManager = $inventorySyncManager;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * After a product import, we need to schedule a full sync to flow
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $productSkus = [];
        $deletedProductsData = (array)$observer->getData('bunch');
        foreach ($deletedProductsData as $deletedProductData) {
            if (array_key_exists(ProductImport::COL_SKU, $deletedProductData)) {
                array_push($productSkus, $deletedProductData[ProductImport::COL_SKU]);
            }
        }
        if (count($productSkus)) {
            $this->logger->info(
                'Flow Catalog Sync - After product import (delete), deferring to product sync manager',
                ['product_skus' => $productSkus]
            );
            $this->syncSkuManager->enqueueMultipleProductsByProductSku($productSkus);
        }
    }
}
