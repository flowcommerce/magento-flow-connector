<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use FlowCommerce\FlowConnector\Api\InventorySyncManagementInterface;
use FlowCommerce\FlowConnector\Api\InventorySyncRepositoryInterface as InventorySyncRepository;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterfaceFactory as InventorySyncFactory;
use FlowCommerce\FlowConnector\Exception\InventorySyncException;
use FlowCommerce\FlowConnector\Model\Api\Inventory\Updates as InventoryUpdatesApiClient;
use FlowCommerce\FlowConnector\Model\Util as FlowUtil;
use GuzzleHttp\Exception\ClientException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory as StockItemCriteriaFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface as StockItemRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class InventorySyncManager
 * @package FlowCommerce\FlowConnector\Model
 */
class InventorySyncManager implements InventorySyncManagementInterface
{
    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory = null;

    /**
     * @var InventorySyncFactory
     */
    private $inventorySyncFactory = null;

    /**
     * @var InventorySyncRepository
     */
    private $inventorySyncRepository = null;

    /**
     * @var InventorySync[]
     */
    private $inventorySyncsToUpdate = null;

    /**
     * @var InventoryUpdatesApiClient
     */
    private $itemUpdateApiClient = null;

    /**
     * @var Logger
     */
    private $logger = null;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory = null;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder = null;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder = null;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository = null;

    /**
     * @var StockItemCriteriaFactory
     */
    private $stockItemCriteriaFactory = null;

    /**
     * @var StoreManager
     */
    private $storeManager = null;

    /**
     * @var FlowUtil
     */
    private $util = null;

    /**
     * InventorySyncManager constructor.
     * @param DateTimeFactory $dateTimeFactory
     * @param InventorySyncFactory $inventorySyncFactory
     * @param InventorySyncRepository $inventorySyncRepository
     * @param InventoryUpdatesApiClient $itemUpdateApiClient
     * @param Logger $logger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param StockItemCriteriaFactory $stockItemCriteriaFactory
     * @param StockItemRepository $stockItemRepository
     * @param StoreManager $storeManager
     * @param FlowUtil $util
     */
    public function __construct(
        DateTimeFactory $dateTimeFactory,
        InventorySyncFactory $inventorySyncFactory,
        InventorySyncRepository $inventorySyncRepository,
        InventoryUpdatesApiClient $itemUpdateApiClient,
        Logger $logger,
        ProductCollectionFactory $productCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        StockItemCriteriaFactory $stockItemCriteriaFactory,
        StockItemRepository $stockItemRepository,
        StoreManager $storeManager,
        FlowUtil $util
    ) {
        $this->dateTimeFactory = $dateTimeFactory;
        $this->inventorySyncFactory = $inventorySyncFactory;
        $this->inventorySyncRepository = $inventorySyncRepository;
        $this->itemUpdateApiClient = $itemUpdateApiClient;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->storeManager = $storeManager;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->util = $util;
    }

    /**
     * Given an array of InventorySyncs, loads them with stock items and associated products
     * @param InventorySync[] $inventorySyncs
     * @return void
     */
    private function assignStockItemsToInventorySyncs($inventorySyncs)
    {
        $productIdsByStore = [];
        foreach ($inventorySyncs as $inventorySync) {
            if (!array_key_exists($inventorySync->getStoreId(), $productIdsByStore)) {
                $productIdsByStore[$inventorySync->getStoreId()] = [];
            }
            array_push($productIdsByStore[$inventorySync->getStoreId()], $inventorySync->getProductId());
        }

        $productsIndexedById = [];
        $stockItemsIndexedById = [];
        foreach ($productIdsByStore as $storeId => $productIds) {
            // It's not possible to get a product list using the repository taking
            // the store id into consideration. Need to fallback to using the collection directly
            $productCollection = $this->productCollectionFactory->create();
            $productCollection
                ->setStoreId($storeId)
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', ['in' => $productIds]);

            /** @var ProductInterface $product */
            foreach ($productCollection->getItems() as $product) {
                $productsIndexedById[$storeId][$product->getId()] = $product;
            }

            $stockItemCriteria = $this->stockItemCriteriaFactory->create();
            $stockItemCriteria->setProductsFilter($productIds);
            $stockItems = $this->stockItemRepository->getList($stockItemCriteria);

            /** @var ProductInterface $product */
            foreach ($stockItems->getItems() as $stockItem) {
                $stockItemsIndexedById[$storeId][$stockItem->getProductId()] = $stockItem;
            }
        }

        foreach ($inventorySyncs as $inventorySync) {
            $productId = $inventorySync->getProductId();
            $storeId = $inventorySync->getStoreId();
            if (array_key_exists($productId, $productsIndexedById[$storeId])) {
                $inventorySync->setProduct($productsIndexedById[$storeId][$productId]);
            }
            if (array_key_exists($productId, $stockItemsIndexedById[$storeId])) {
                $inventorySync->setStockItem($stockItemsIndexedById[$storeId][$productId]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldQueueDoneItems()
    {
        $now = $this->dateTimeFactory->create('now');
        $now->modify('-3 days');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(InventorySync::DATA_KEY_STATUS, InventorySync::STATUS_DONE)
            ->addFilter(InventorySync::DATA_KEY_UPDATED_AT, $now, 'lt')
            ->create();
        $oldDoneItems = $this->inventorySyncRepository->getList($searchCriteria);
        $this->inventorySyncRepository->deleteMultiple($oldDoneItems->getItems());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueueErrorDoneItems()
    {
        $this->inventorySyncRepository->deleteQueueErrorDoneItems();
    }

    /**
     * {@inheritdoc}
     */
    private function getNextInventorySyncBatchToBeProcessed($batchSize)
    {
        $sortOrderPriority = $this->sortOrderBuilder
            ->setAscendingDirection()
            ->setField(InventorySync::DATA_KEY_PRIORITY)
            ->create();
        $sortOrderUpdatedAt = $this->sortOrderBuilder
            ->setAscendingDirection()
            ->setField(InventorySync::DATA_KEY_UPDATED_AT)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(InventorySync::DATA_KEY_STATUS, InventorySync::STATUS_NEW, 'eq')
            ->addSortOrder($sortOrderPriority)
            ->addSortOrder($sortOrderUpdatedAt)
            ->setPageSize($batchSize)
            ->create();
        $inventorySyncList = $this->inventorySyncRepository->getList($searchCriteria);

        if ($inventorySyncList->getTotalCount() > 0) {
            /** @var InventorySync[] $items */
            $items = $inventorySyncList->getItems();
            $this->assignStockItemsToInventorySyncs($items);
        }
        return $inventorySyncList;
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueAllStockItems()
    {
        $this->logger->info('Queueing all products for sync to Flow.');
        $this->resetOldQueueProcessingItems();
        $this->deleteQueueErrorDoneItems();
        $this->deleteOldQueueDoneItems();

        // Get list of stores with enabled connectors
        $storeIds = [];
        /** @var Store $store */
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->util->isFlowEnabled($store->getStoreId())) {
                array_push($storeIds, $store->getStoreId());
                $this->logger->info('Including stock items from store: ' . $store->getName() .
                    ' [id=' . $store->getStoreId() . ']');
            } else {
                $this->logger->info('Not including stock items from store: ' . $store->getName() .
                    ' [id=' . $store->getStoreId() . '] - Flow disabled');
            }
        }

        if (count($storeIds) > 0) {
            $this->enqueueAllStockItemsFromStores($storeIds);
        } else {
            $this->logger->info('Flow connector disabled on all stores, zero items queued.');
        }
    }

    /**
     * Defers enqueueing of all stock items from enabled stores
     * @param int[] $storeIds
     * @throws CouldNotSaveException
     */
    private function enqueueAllStockItemsFromStores(array $storeIds)
    {
        $inventorySyncs = [];
        foreach ($storeIds as $storeId) {
            $searchCriteria = $this->stockItemCriteriaFactory->create();
            $stockItems = $this->stockItemRepository->getList($searchCriteria);
            /** @var StockItemInterface $stockItem */
            foreach ($stockItems->getItems() as $stockItem) {
                /** @var InventorySync $inventorySync */
                $inventorySync = $this->inventorySyncFactory->create();
                $inventorySync->setStoreId($storeId);
                $inventorySync->setProductId($stockItem->getProductId());
                $inventorySync->setStatus(InventorySync::STATUS_NEW);
                array_push($inventorySyncs, $inventorySync);
            }
        }

        $this->inventorySyncRepository->saveMultiple($inventorySyncs);
    }

    /**
     * {@inheritdoc}
     */
    public function enqueueStockItem(StockItemInterface $stockItem)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(InventorySync::DATA_KEY_PRODUCT_ID, $stockItem->getProductId(), 'eq')
            ->addFilter(InventorySync::DATA_KEY_STATUS, InventorySync::STATUS_NEW, 'eq')
            ->create();
        $existingStockItems = $this->inventorySyncRepository->getList($searchCriteria);
        if ($existingStockItems->getTotalCount() == 0) {
            $inventorySync = $this->inventorySyncFactory->create();
            $inventorySync->setProductId($stockItem->getProductId());
            $inventorySync->setStatus(InventorySync::STATUS_NEW);
            $this->inventorySyncRepository->save($inventorySync);
        }
    }

    /**
     * Marks InventorySync as error after a sync attempt
     * @param ClientException $reason
     * @param $index
     */
    public function failureInventoryUpdateCallback($reason, $index)
    {
        if (array_key_exists($index, $this->inventorySyncsToUpdate)) {
            $this->markInventorySyncAsError($this->inventorySyncsToUpdate[$index], $reason->getMessage());
            unset($this->inventorySyncsToUpdate[$index]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function markInventorySyncAsDone(InventorySync $inventorySync)
    {
        $inventorySync->setStatus(InventorySync::STATUS_DONE);
        $this->inventorySyncRepository->save($inventorySync);
    }

    /**
     * {@inheritdoc}
     */
    public function markInventorySyncAsError(InventorySync $inventorySync, $errorMessage = null)
    {
        $inventorySync->setStatus(InventorySync::STATUS_ERROR);
        $inventorySync->setMessage($errorMessage);
        $this->inventorySyncRepository->save($inventorySync);
    }

    /**
     * {@inheritdoc}
     */
    public function markInventorySyncAsProcessing(InventorySync $inventorySync)
    {
        $inventorySync->setStatus(InventorySync::STATUS_PROCESSING);
        $this->inventorySyncRepository->save($inventorySync);
    }

    /**
     * {@inheritdoc}
     */
    public function process($numToProcess = 1000, $keepAlive = 60)
    {
        try {
            $this->logger->info('Starting inventory sync processing');

            while ($keepAlive > 0) {
                while ($numToProcess != 0) {
                    $ts = microtime(true);
                    $inventorySyncs = $this->getNextInventorySyncBatchToBeProcessed($numToProcess);
                    $this->logger->info('Time to load inventory syncs batch: ' . (microtime(true) - $ts));

                    if ((int) $inventorySyncs->getTotalCount() === 0) {
                        $this->logger->info('No records to process.');
                        break;
                    }

                    $this->inventorySyncsToUpdate = [];

                    foreach ($inventorySyncs->getItems() as $inventorySync) {
                        $product = $inventorySync->getProduct();
                        if (!$this->util->isFlowEnabled($inventorySync->getStoreId())) {
                            $this->markInventorySyncAsError($inventorySync, 'Flow module is disabled.');
                            continue;
                        }
                        if ($product) {
                            $this->markInventorySyncAsProcessing($inventorySync);
                            array_push($this->inventorySyncsToUpdate, $inventorySync);
                        }
                        $numToProcess -= 1;
                    }

                    if (count($this->inventorySyncsToUpdate)) {
                        $ts = microtime(true);
                        $this->itemUpdateApiClient->execute(
                            $this->inventorySyncsToUpdate,
                            [$this, 'successfulInventoryUpdateCallback'],
                            [$this, 'failureInventoryUpdateCallback']
                        );
                        $this->logger->info('Time to asynchronously update inventory on flow.io: '
                            . (microtime(true) - $ts));
                    }
                }

                if ($numToProcess == 0) {
                    // We've hit the processing limit, break out of loop.
                    break;
                }

                // Num to process not exhausted, keep alive to wait for more.
                $keepAlive -= 1;
                sleep(1);
            }

            $this->logger->info('Done processing inventory sync queue.');
        } catch (\Exception $e) {
            $this->logger->warning('Error syncing inventory: '
                . $e->getMessage() . '\n' . $e->getTraceAsString());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetOldQueueProcessingItems()
    {
        $now = $this->dateTimeFactory->create('now');
        $date = $now->modify('-4 hours');

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(InventorySync::DATA_KEY_STATUS, InventorySync::STATUS_DONE)
            ->addFilter(InventorySync::DATA_KEY_UPDATED_AT, $date, 'lt')
            ->create();
        $oldProcessingItems = $this->inventorySyncRepository->getList($searchCriteria);
        $this->inventorySyncRepository->updateMultipleStatuses(
            $oldProcessingItems->getItems(),
            InventorySync::STATUS_NEW
        );
    }

    /**
     * Marks SyncSku as processed
     * @param $response
     * @param $index
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function successfulInventoryUpdateCallback($response, $index)
    {
        if (array_key_exists($index, $this->inventorySyncsToUpdate)) {
            $inventorySync = $this->inventorySyncsToUpdate[$index];
            $this->markInventorySyncAsDone($inventorySync);
            unset($this->inventorySyncsToUpdate[$index]);
        }
    }
}
