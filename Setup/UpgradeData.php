<?php
namespace FlowCommerce\FlowConnector\Setup;

use Magento\Customer\Model\Customer;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\State as AppState;
use Magento\Framework\App\Area as AppArea;

class UpgradeData implements UpgradeDataInterface
{
    private $salesSetupFactory;

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
     * UpgradeData constructor.
     * @param SalesSetupFactory $salesSetupFactory
     * @param StoreManager $storeManager
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        StoreManager $storeManager,
        CustomerRepository $customerRepository,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataObjectHelper $dataObjectHelper,
        IndexerRegistry $indexerRegistry,
        Logger $logger,
        AppState $appState
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->indexerRegistry = $indexerRegistry;
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        if ($context->getVersion() && version_compare($context->getVersion(), '1.0.2', '<')) {
            $attributes = [
                'base_duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
                'duty' => ['type' => 'decimal', 'visible' => false, 'required' => false],
            ];

            foreach ($attributes as $attributeCode => $attributeParams) {
                $salesSetup->addAttribute('order', $attributeCode, $attributeParams);
            }
        }

        if ($context->getVersion() && version_compare($context->getVersion(), '1.0.29', '<')) {
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
                    } catch (\Exception $e) {
                        $this->logger->warn(
                            sprintf(
                                'Error while saving customer after adjusting website_id for customer %s: %s \n %s',
                                $customerDataObject->getId(),
                                $e->getMessage(),
                                $e->getTraceAsString()
                            )
                        );
                    }

                    try {
                        /** @var IndexerInterface $indexer */
                        $indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
                        $indexer->reindexRow($customerDataObject->getId());
                    } catch (\Exception $e) {
                        $this->logger->warn(
                            sprintf(
                                'Error while reindexing after adjusting website_id for customer %s: %s \n %s',
                                $customerDataObject->getId(),
                                $e->getMessage(),
                                $e->getTraceAsString()
                            )
                        );
                    }
                }
            }
        }
    }
}
