<?php

namespace FlowCommerce\FlowConnector\Observer;

use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\CatalogImportExport\Model\Import\Product as ProductImport;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\Configuration;

/**
 * Class SyncProductsAfterImportDelete
 * @package FlowCommerce\FlowConnector\Observer
 */
class SyncProductsAfterImportDelete implements ObserverInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Action constructor.
     * @param Logger $logger
     * @param SyncSkuManager $syncSkuManager
     * @param Configuration $configuration
     * @return void
     */
    public function __construct(
        Logger $logger,
        SyncSkuManager $syncSkuManager,
        Configuration $configuration
    ) {
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
        $this->configuration = $configuration;
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
            foreach ($this->configuration->getEnabledStores() as $store) {
                $this->syncSkuManager->enqueueMultipleProductsByProductSku($productSkus, $store->getId());
            }
        }
    }
}
