<?php

namespace FlowCommerce\FlowConnector\Model;

use \FlowCommerce\FlowConnector\Api\Data\SyncSkuSearchResultsInterfaceFactory as SearchResultFactory;
use \FlowCommerce\FlowConnector\Api\SyncSkuManagementInterface;
use \FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku as SyncSkuResourceModel;
use \FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\CollectionFactory as SyncSkuCollectionFactory;
use \FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\Collection as SyncSkuCollection;
use \Magento\Catalog\Api\Data\ProductInterface;
use \Magento\Catalog\Model\ProductRepository;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Store\Model\Store;
use \Magento\Store\Model\StoreManager;
use \Psr\Log\LoggerInterface as Logger;

/**
 * Class SyncSkuManagement
 * @package FlowCommerce\FlowConnector\Model
 */
class SyncSkuManager implements SyncSkuManagementInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var SyncSkuCollectionFactory
     */
    private $syncSkuCollectionFactory;

    /**
     * @var SyncSkuResourceModel
     */
    private $syncSkuResourceModel;

    /**
     * @var Util
     */
    private $util;

    /**
     * SyncSkuManagement constructor.
     * @param SearchResultFactory $searchResultFactory
     * @param SyncSkuResourceModel $syncSkuResourceModel
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepository $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SyncSkuCollectionFactory $syncSkuCollectionFactory
     * @param StoreManager $storeManager
     * @param Util $util
     * @param Logger $logger
     */
    public function __construct(
        SearchResultFactory $searchResultFactory,
        SyncSkuResourceModel $syncSkuResourceModel,
        ProductCollectionFactory $productCollectionFactory,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SyncSkuCollectionFactory $syncSkuCollectionFactory,
        StoreManager $storeManager,
        Util $util,
        Logger $logger
    ) {
        $this->storeManager = $storeManager;
        $this->searchResultFactory = $searchResultFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->syncSkuCollectionFactory = $syncSkuCollectionFactory;
        $this->syncSkuResourceModel = $syncSkuResourceModel;
        $this->util = $util;
        $this->logger = $logger;
    }

    /**
     * @param SyncSku[] $syncSkus
     * @return SyncSku[]
     */
    private function assignProductsToSyncSkus($syncSkus)
    {
        $productSkusByStore = [];
        foreach ($syncSkus as $syncSku) {
            if (!array_key_exists($syncSku->getStoreId(), $productSkusByStore)) {
                $productSkusByStore[$syncSku->getStoreId()] = [];
            }
            array_push($productSkusByStore[$syncSku->getStoreId()], $syncSku->getSku());
        }

        $productsIndexedBySku = [];
        foreach ($productSkusByStore as $storeId => $productSkus) {
            $this->storeManager->setCurrentStore($storeId);

            // It's not possible to get a product list using the repository taking
            // the store id into consideration. Need to fallback to using the collection directly
            $productCollection = $this->productCollectionFactory->create();
            $productCollection
                ->setStoreId($storeId)
                ->addAttributeToSelect('*')
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addAttributeToFilter(ProductInterface::SKU, ['in' => $productSkus]);

            /** @var ProductInterface $product */
            foreach ($productCollection->getItems() as $product) {
                $productsIndexedBySku[$storeId][$product->getSku()] = $product;
            }
        }

        foreach ($syncSkus as $syncSku) {
            $productSku = $syncSku->getSku();
            $storeId = $syncSku->getStoreId();
            if (array_key_exists($productSku, $productsIndexedBySku[$storeId])) {
                $syncSku->setProduct($productsIndexedBySku[$storeId][$productSku]);
            }
        }

        return $syncSkus;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldQueueDoneItems()
    {
        $this->syncSkuResourceModel->deleteOldQueueDoneItems();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueueErrorDoneItems()
    {
        $this->syncSkuResourceModel->deleteQueueErrorDoneItems();
    }

    /**
     * Deletes given SyncSku
     * @param SyncSku $syncSku
     */
    public function deleteSyncSku(SyncSku $syncSku)
    {
        $ts = microtime(true);
        $syncSku->setStatus(SyncSku::STATUS_DONE);
        $this->saveSyncSku($syncSku);
        $this->logger->info('Time to delete sync sku: ' . (microtime(true) - $ts));
    }

    /**
     * {@inheritdoc}
     */
    public function getNextSyncSkuBatchToBeProcessed($batchSize)
    {
        $batch = $this->searchResultFactory->create();

        /** @var SyncSkuCollection $collection */
        $collection = $this->syncSkuCollectionFactory->create();
        $collection->addFieldToFilter('status', SyncSku::STATUS_NEW);
        $collection->setOrder('priority', 'ASC');
        $collection->setOrder('updated_at', 'ASC');
        $collection->setPageSize($batchSize);
        $collection->load();

        if ($collection->getSize() > 0) {
            $items = $collection->getItems();
            $this->assignProductsToSyncSkus($items);
            $batch->setItems($items);
            $batch->setTotalCount($collection->count());
        }
        return $batch;
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueAllProducts()
    {
        $this->logger->info('Queueing all products for sync to Flow.');
        $this->syncSkuResourceModel->resetOldQueueProcessingItems();
        $this->syncSkuResourceModel->deleteQueueErrorDoneItems();
        $this->syncSkuResourceModel->deleteOldQueueDoneItems();

        // Get list of stores with enabled connectors
        $storeIds = [];
        /** @var Store $store */
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->util->isFlowEnabled($store->getStoreId())) {
                array_push($storeIds, $store->getStoreId());
                $this->logger->info('Including products from store: ' . $store->getName() .
                    ' [id=' . $store->getStoreId() . ']');
            } else {
                $this->logger->info('Not including products from store: ' . $store->getName() .
                    ' [id=' . $store->getStoreId() . '] - Flow disabled');
            }
        }

        if (count($storeIds) > 0) {
            $this->syncSkuResourceModel->enqueueAllProductsFromStores($storeIds);
        } else {
            $this->logger->info('Flow connector disabled on all stores, zero items queued.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function markSyncSkuAsDone(SyncSku $syncSku)
    {
        $syncSku->setStatus(SyncSku::STATUS_DONE);
        $ts = microtime(true);
        $this->saveSyncSku($syncSku);
        $this->logger->info('Time to update sync sku as done: ' . (microtime(true) - $ts));
    }

    /**
     * {@inheritdoc}
     */
    public function markSyncSkuAsError(SyncSku $syncSku, $errorMessage = null)
    {
        $ts = microtime(true);
        $syncSku->setStatus(SyncSku::STATUS_ERROR);
        if ($errorMessage !== null) {
            $syncSku->setMessage(substr($errorMessage, 0, 200));
        }
        $this->saveSyncSku($syncSku);
        $this->logger->info('Time to update sync sku as error: ' . (microtime(true) - $ts));
    }

    /**
     * {@inheritdoc}
     */
    public function markSyncSkuAsProcessing(SyncSku $syncSku)
    {
        $syncSku->setStatus(SyncSku::STATUS_PROCESSING);
        $ts = microtime(true);
        $this->saveSyncSku($syncSku);
        $this->logger->info('Time to update sync sku for processing: ' . (microtime(true) - $ts));
    }

    /**
     * {@inheritdoc}
     */
    public function resetOldQueueProcessingItems()
    {
        $this->syncSkuResourceModel->resetOldQueueProcessingItems();
    }

    /**
     * Helper method to update
     */
    private function saveSyncSku($syncSku)
    {
        $this->syncSkuResourceModel->update($syncSku);
    }

    /**
     * Allows logger to be overridden
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
}