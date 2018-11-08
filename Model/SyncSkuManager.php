<?php

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use FlowCommerce\FlowConnector\Api\Data\SyncSkuInterface;
use FlowCommerce\FlowConnector\Api\Data\SyncSkuSearchResultsInterfaceFactory as SearchResultFactory;
use FlowCommerce\FlowConnector\Api\SyncSkuManagementInterface;
use FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku as SyncSkuResourceModel;
use FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\Collection as SyncSkuCollection;
use FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\CollectionFactory as SyncSkuCollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory as ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface as Logger;

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
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var ProductInterface[]
     */
    private $productsByProductId = [];

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
     * @var SyncSkuFactory
     */
    private $syncSkuFactory;

    /**
     * @var Util
     */
    private $util;

    /**
     * SyncSkuManagement constructor.
     * @param Logger $logger
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductFactory $productFactory
     * @param ProductRepository $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SearchResultFactory $searchResultFactory
     * @param StoreManager $storeManager
     * @param SyncSkuCollectionFactory $syncSkuCollectionFactory
     * @param SyncSkuFactory $syncSkuFactory
     * @param SyncSkuResourceModel $syncSkuResourceModel
     * @param Util $util
     */
    public function __construct(
        Logger $logger,
        ProductCollectionFactory $productCollectionFactory,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SearchResultFactory $searchResultFactory,
        StoreManager $storeManager,
        SyncSkuCollectionFactory $syncSkuCollectionFactory,
        SyncSkuFactory $syncSkuFactory,
        SyncSkuResourceModel $syncSkuResourceModel,
        Util $util
    ) {
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchResultFactory = $searchResultFactory;
        $this->storeManager = $storeManager;
        $this->syncSkuCollectionFactory = $syncSkuCollectionFactory;
        $this->syncSkuFactory = $syncSkuFactory;
        $this->syncSkuResourceModel = $syncSkuResourceModel;
        $this->util = $util;
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
            if (array_key_exists($storeId, $productsIndexedBySku) &&
                array_key_exists($productSku, $productsIndexedBySku[$storeId])
            ) {
                $syncSku->setProduct($productsIndexedBySku[$storeId][$productSku]);
            }
        }

        return $syncSkus;
    }

    /**
     * Deletes given SyncSku
     * @param SyncSku $syncSku
     * @throws \Exception
     */
    public function deleteSyncSku(SyncSku $syncSku)
    {
        $ts = microtime(true);
        $syncSku->delete();
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
     * Enqueues product for syncing to Flow.
     * @param ProductInterface $product
     * @return void
     * @throws Exception
     */
    public function enqueue(ProductInterface $product)
    {
        if ($product->getStoreId() == 0) {
            // Global store, queue product for all valid stores
            foreach($this->util->getEnabledStores() as $store) {
                $product2 = $this->productFactory->create()->setStoreId($store->getId())->load($product->getId());
                if ($product2->getId() != null) {
                    $this->logger->info('queuing product2');
                    $this->enqueue($product2);
                }
            }

        } else if (!$this->util->isFlowEnabled($product->getStoreId())) {
            $this->logger->info('Product store does not have Flow enabled, skipping: ' . $product->getSku());

        } else {
            /** @var SyncSku $syncSku */
            $syncSku = $this->syncSkuFactory->create();

            // Check if product is queued
            $collection = $syncSku->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('sku', $product->getSku())
                ->addFieldToFilter('store_id', $product->getStoreid())
                ->setPageSize(1);

            // Only queue if product is not already queued.
            $shouldSyncChildren = $this->shouldSyncChildren($product);
            if ($collection->getSize() == 0) {
                $syncSku->setStatus(SyncSku::STATUS_NEW);
                $syncSku->setState(SyncSku::STATE_NEW);
                $syncSku->setSku($product->getSku());
                $syncSku->setStoreId($product->getStoreId());
                $syncSku->setShouldSyncChildren($shouldSyncChildren);
                $syncSku->save();
                $this->logger
                    ->info('Queued new product for sync: ' . $product->getSku() . ', storeId: ' . $product->getStoreId());
            } else {
                /** @var SyncSku $existingSyncSku */
                $existingSyncSku = $collection->getFirstItem();
                if($existingSyncSku->getStatus() != SyncSku::STATUS_NEW)  {
                    $existingSyncSku->setStatus(SyncSku::STATUS_NEW);
                    if($existingSyncSku->isShouldSyncChildren() !== $shouldSyncChildren) {
                        $existingSyncSku->setShouldSyncChildren($shouldSyncChildren);
                    }

                    $existingSyncSku->save();

                    $this->logger
                        ->info('Queued existing product for sync: ' . $product->getSku() . ', storeId: ' . $product->getStoreId());
                } else {
                    $this->logger
                        ->info(
                            'Product already queued, skipping: ' . $product->getSku() . ', storeId: ' .
                            $product->getStoreId()
                        );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueAllProducts()
    {
        $this->logger->info('Queueing all products for sync to Flow.');
        $this->syncSkuResourceModel->resetOldQueueProcessingItems();

        // Get list of stores with enabled connectors
        $storeIds = [];
        /** @var Store $store */
        foreach ($this->util->getEnabledStores() as $store) {
            array_push($storeIds, $store->getStoreId());
            $this->logger->info('Including products from store: ' . $store->getName() .
                ' [id=' . $store->getStoreId() . ']');
        }

        if (count($storeIds) > 0) {
            $this->syncSkuResourceModel->enqueueAllProductsFromStores($storeIds);
        } else {
            $this->logger->info('Flow connector disabled on all stores, zero items queued.');
        }
    }

    /**
     * Enqueues multiple products by product ids
     * @param int[] $productIds
     * @return void
     * @throws
     */
    public function enqueueMultipleProductsByProductIds($productIds)
    {
        $this->preloadProductsByProductIds($productIds);
        foreach ($productIds as $productId) {
            try {
                $product = $this->getProductFromProductId($productId);
                if ($product !== null) {
                    $this->enqueue($product);
                }
            } catch (Exception $e) {
                $this->logger->error(
                    'Error while enqueueing product for Flow.io sync. Product ID: ' .
                    $productId,
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Enqueues multiple products by product skus
     * @param string[] $productSkus
     * @param int|null $storeId
     * @return void
     * @throws
     */
    public function enqueueMultipleProductsByProductSku($productSkus, $storeId = null)
    {
        if ($storeId === null) {
            $storeId = 0;
        }

        foreach ($productSkus as $productSku) {
            try {
                $syncSku = $this->syncSkuFactory->create();
                // Check if product is queued and unprocessed.
                $collection = $syncSku->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter(SyncSkuInterface::DATA_KEY_SKU, $productSku)
                    ->addFieldToFilter(SyncSkuInterface::DATA_KEY_STORE_ID, $storeId)
                    ->setPageSize(1);

                if ($collection->getSize() == 0) {
                    $syncSku->setStatus(SyncSku::STATUS_NEW);
                    $syncSku->setState(SyncSku::STATE_NEW);
                    $syncSku->setSku($productSku);
                    $syncSku->setStoreId($storeId);
                    $syncSku->save();
                    $this->logger
                        ->info('Queued new product for sync: ' . $productSku . ', storeId: ' . $storeId);
                } else {
                    /** @var SyncSku $existingSyncSku */
                    $existingSyncSku = $collection->getFirstItem();
                    if($existingSyncSku->getStatus() != SyncSku::STATUS_NEW)  {
                        $existingSyncSku->setStatus(SyncSku::STATUS_NEW);

                        $existingSyncSku->save();

                        $this->logger
                            ->info('Queued existing product for sync: ' . $productSku . ', storeId: ' . $storeId);
                    } else {
                        $this->logger
                            ->info('Product already queued, skipping: ' . $productSku . ', storeId: ' . $storeId);
                    }
                }
            } catch (Exception $e) {
                $this->logger->error(
                    'Error while enqueueing product for Flow.io sync. Product SKU: ' .
                    $productSku,
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Given a product id, returns it's associated product model if available
     * @param int $productId
     * @return ProductInterface|null
     */
    private function getProductFromProductId($productId)
    {
        $return = null;
        if (array_key_exists($productId, $this->productsByProductId)) {
            $return = $this->productsByProductId[$productId];
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function markSyncSkuAsDone(
        SyncSku $syncSku,
        $requestUrl = null,
        $requestBody = null,
        $responseHeaders = null,
        $responseBody = null
    ) {
        $syncSku->setStatus(SyncSku::STATUS_DONE);
        $syncSku->setState(SyncSku::STATE_DONE);
        $syncSku->setMessage(null);
        $syncSku->setRequestUrl($requestUrl);
        $syncSku->setRequestBody($requestBody);
        $syncSku->setResponseHeaders($responseHeaders);
        $syncSku->setResponseBody($responseBody);
        $ts = microtime(true);
        $this->updateSyncSkuStatus($syncSku);
        $this->logger->info('Time to update sync sku as done: ' . (microtime(true) - $ts));
    }

    /**
     * {@inheritdoc}
     */
    public function markSyncSkuAsError(
        SyncSku $syncSku,
        $errorMessage = null,
        $requestUrl = null,
        $requestBody = null,
        $responseHeaders = null,
        $responseBody = null
    ) {
        $ts = microtime(true);
        $syncSku->setStatus(SyncSku::STATUS_ERROR);
        if ($errorMessage !== null) {
            $syncSku->setMessage((string)$errorMessage);
        }
        $syncSku->setRequestUrl($requestUrl);
        $syncSku->setRequestBody($requestBody);
        $syncSku->setResponseHeaders($responseHeaders);
        $syncSku->setResponseBody($responseBody);
        $this->updateSyncSkuStatus($syncSku);
        $this->logger->info('Time to update sync sku as error: ' . (microtime(true) - $ts));
    }

    /**
     * {@inheritdoc}
     */
    public function markSyncSkuAsProcessing(SyncSku $syncSku)
    {
        $syncSku->setStatus(SyncSku::STATUS_PROCESSING);
        $ts = microtime(true);
        $this->updateSyncSkuStatus($syncSku);
        $this->logger->info('Time to update sync sku for processing: ' . (microtime(true) - $ts));
    }

    /**
     * Given a list of product ids, loads all product models and store them in memory for
     * posterior use
     * @param int[] $productIds
     * @return void
     */
    private function preloadProductsByProductIds(array $productIds)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $productIds, 'in')
            ->create();
        $products = $this->productRepository->getList($searchCriteria);
        foreach ($products->getItems() as $product) {
            $productId = $product->getId();
            $this->productsByProductId[$productId] = $product;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetOldQueueProcessingItems()
    {
        $this->syncSkuResourceModel->resetOldQueueProcessingItems();
    }

    /**
     * Allows logger to be overridden
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Decides for a given product if children products should be synced. Currently handles only configurable products
     * @param ProductInterface $product
     * @return bool
     */
    private function shouldSyncChildren(ProductInterface $product)
    {
        $return = false;
        if ($product->getTypeId() === ConfigurableType::TYPE_CODE) {

            // Temporary hack to ensure that all child products are synced every time since shouldSyncChildren logic
            // we have below might not be working correctly at this moment with catalog_product_save_before or
            // catalog_product_save_after.
            // TODO: Double check shouldSyncChildren logic below
            $return = true;

//            if ($product->isObjectNew()) {
//                $return = true;
//            } else {
//                // We're dealing with an update of a configurable product
//                // We only want to sync it's children when the links to it's children have changed
//                $originalChildProductIds = [];
//                /** @var ProductInterface $childProduct */
//                foreach ($product->getTypeInstance()->getUsedProducts($product) as $childProduct) {
//                    array_push($originalChildProductIds, $childProduct->getId());
//                }
//                $currentChildProductIds = (array)$product->getExtensionAttributes()->getConfigurableProductLinks();
//                sort($originalChildProductIds);
//                sort($currentChildProductIds);
//                $return = ($originalChildProductIds !== $currentChildProductIds);
//            }
        }

        return $return;
    }

    /**
     * Helper method to update
     * @param SyncSkuInterface $syncSku
     */
    private function updateSyncSkuStatus($syncSku)
    {
        $this->syncSkuResourceModel->updateStatus($syncSku);
    }
}
