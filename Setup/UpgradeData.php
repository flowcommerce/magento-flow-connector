<?php
namespace FlowCommerce\FlowConnector\Setup;

use Magento\Customer\Model\Customer;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area as AppArea;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * UpgradeData constructor.
     * @param StoreManager $storeManager
     * @param CustomerRepository $customerRepository
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataObjectHelper $dataObjectHelper
     * @param IndexerRegistry $indexerRegistry
     * @param Logger $logger
     * @param AppState $appState
     */
    public function __construct(
        StoreManager $storeManager,
        CustomerRepository $customerRepository,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataObjectHelper $dataObjectHelper,
        IndexerRegistry $indexerRegistry,
        Logger $logger,
        AppState $appState,
        SyncSkuManager $syncSkuManager
    ) {
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->indexerRegistry = $indexerRegistry;
        $this->logger = $logger;
        $this->appState = $appState;
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if ($context->getVersion() && version_compare($context->getVersion(), '1.0.29', '<')) {
            $this->fixCustomerWebsiteIdToMatchStoreId($setup);
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '1.0.36', '<')) {
            $this->enqueueAllProducts($setup);
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function fixCustomerWebsiteIdToMatchStoreId(ModuleDataSetupInterface $setup)
    {
        $this->appState->setAreaCode(AppArea::AREA_ADMINHTML);

        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $filterGroupOne = [];
            $filterGroupTwo = [];

            // Get customers whose website_id does not match store_id and adjust website_id
            $filterGroupOne[] = $this->filterBuilder
                ->setField('store_id')
                ->setValue($store->getId())
                ->setConditionType('eq')
                ->create();

            $filterGroupTwo[] = $this->filterBuilder
                ->setField('website_id')
                ->setValue($store->getWebsiteId())
                ->setConditionType('neq')
                ->create();

            $filterGroupOneGroup = $this->filterGroupBuilder
                ->setFilters($filterGroupOne)
                ->create();

            $filterGroupTwoGroup = $this->filterGroupBuilder
                ->setFilters($filterGroupTwo)
                ->create();

            $customerSearchCriteria = $this->searchCriteriaBuilder
                ->setFilterGroups([
                    $filterGroupOneGroup,
                    $filterGroupTwoGroup
                ])
                ->create();

            $customerSearchResults = $this->customerRepository->getList($customerSearchCriteria);

            foreach ($customerSearchResults->getItems() as $customerDataObject) {
                // Mark applicants as approved and set them into appropriate customer group
                $newCustomerData = [
                    'website_id' => $store->getWebsiteId()
                ];

                $this->dataObjectHelper->populateWithArray(
                    $customerDataObject,
                    $newCustomerData,
                    '\Magento\Customer\Api\Data\CustomerInterface'
                );

                try {
                    $this->customerRepository->save($customerDataObject);

                    /** @var IndexerInterface $indexer */
                    $indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
                    $indexer->reindexRow($customerDataObject->getId());
                } catch (\Exception $e) {
                    $this->logger->critical(
                        sprintf(
                            'Error while saving customer after adjusting website_id for customer %s: %s \n %s',
                            $customerDataObject->getId(),
                            $e->getMessage(),
                            $e->getTraceAsString()
                        )
                    );
                }
            }
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    private function enqueueAllProducts(ModuleDataSetupInterface $setup)
    {
        try {
            $this->syncSkuManager->enqueueAllProducts();
        } catch (\Exception $e) {
            $this->logger->critical(
                sprintf(
                    'Error while enqueuing all products as part of data upgrade script: %s \n %s',
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );
        }
    }
}

