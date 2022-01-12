<?php

namespace FlowCommerce\FlowConnector\Observer;

use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\CatalogImportExport\Model\Import\Product as ProductImport;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class SyncProductsAfterImportSave
 * @package FlowCommerce\FlowConnector\Observer
 */
class SyncProductsAfterImportSave implements ObserverInterface
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
     * Action constructor.
     * @param Logger $logger
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        Logger $logger,
        SyncSkuManager $syncSkuManager
    ) {
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * After a product import, we need to schedule a full sync to flow
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $productIds = [];
        $importedProductsData = $observer->getData('bunch');
        /** @var ProductImport $adapter */
        $adapter = $observer->getData('adapter');
        $newSkus = (array) $adapter->getNewSku();
        $oldSkus = (array) $adapter->getOldSku();

        foreach ($importedProductsData as $importedProductData) {
            if (array_key_exists('sku', $importedProductData)) {
                $importedProductSku = strtolower($importedProductData['sku']);

                if (array_key_exists($importedProductSku, $newSkus)) {
                    array_push($productIds, $newSkus[$importedProductSku]['entity_id']);
                    continue;
                }

                if (array_key_exists($importedProductSku, $oldSkus)) {
                    array_push($productIds, $oldSkus[$importedProductSku]['entity_id']);
                    continue;
                }
            }
        }

        if (count($productIds)) {
            $this->logger->info(
                'Flow Catalog Sync - After product import, deferring to product and inventory sync managers',
                ['product_ids' => $productIds]
            );
            $this->syncSkuManager->enqueueMultipleProductsByProductIds($productIds);
        }
    }
}
