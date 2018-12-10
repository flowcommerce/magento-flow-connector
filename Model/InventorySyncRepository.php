<?php

namespace FlowCommerce\FlowConnector\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface as CollectionProcessor;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory as SearchResultsFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use FlowCommerce\FlowConnector\Api\InventorySyncRepositoryInterface;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterfaceFactory as InventorySyncFactory;
use FlowCommerce\FlowConnector\Model\ResourceModel\InventorySync as InventorySyncResource;
use FlowCommerce\FlowConnector\Model\ResourceModel\InventorySync\CollectionFactory as InventorySyncCollectionFactory;

/**
 * Class InventorySyncRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventorySyncRepository implements InventorySyncRepositoryInterface
{
    /**
     * @var CollectionProcessor
     */
    private $collectionProcessor;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @var InventorySyncResource
     */
    private $resource;

    /**
     * @var SearchResultsFactory
     */
    private $searchResultsFactory;

    /**
     * @var InventorySyncFactory
     */
    private $inventorySyncFactory;

    /**
     * @var InventorySyncCollectionFactory
     */
    private $inventorySyncCollectionFactory;

    /**
     * @param InventorySyncResource $resource
     * @param InventorySyncFactory $inventorySyncFactory
     * @param InventorySyncCollectionFactory $inventorySyncCollectionFactory
     * @param SearchResultsFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param CollectionProcessor $collectionProcessor
     */
    public function __construct(
        InventorySyncResource $resource,
        InventorySyncFactory $inventorySyncFactory,
        InventorySyncCollectionFactory $inventorySyncCollectionFactory,
        SearchResultsFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        CollectionProcessor $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->inventorySyncFactory = $inventorySyncFactory;
        $this->inventorySyncCollectionFactory = $inventorySyncCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(InventorySync $inventorySync)
    {
        try {
            $this->resource->save($inventorySync);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $inventorySync;
    }

    /**
     * {@inheritdoc}
     */
    public function saveMultiple(array $inventorySyncs)
    {
        try {
            $this->resource->saveMultiple($inventorySyncs);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getById($inventorySyncId)
    {
        $inventorySync = $this->inventorySyncFactory->create();
        $this->resource->load($inventorySync, $inventorySyncId);
        if (!$inventorySync->getId()) {
            throw new NoSuchEntityException(
                __('The Inventory Sync with the "%1" ID doesn\'t exist.', $inventorySyncId)
            );
        }
        return $inventorySync;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->inventorySyncCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults
            ->setSearchCriteria($criteria)
            ->setItems($collection->getItems())
            ->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(InventorySync $inventorySync)
    {
        try {
            $this->resource->delete($inventorySync);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($inventorySyncId)
    {
        return $this->delete($this->getById($inventorySyncId));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $inventorySyncs)
    {
        try {
            $this->resource->deleteMultiple($inventorySyncs);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueueErrorDoneItems()
    {
        try {
            $this->resource->deleteQueueErrorDoneItems();
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function updateMultipleStatuses(array $inventorySyncs, $newStatus)
    {
        try {
            $this->resource->updateMultipleStatuses($inventorySyncs, $newStatus);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByStoreId(int $storeId)
    {
        try {
            $this->resource->deleteByStoreId($storeId);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }
}
