<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use \Exception;
use FlowCommerce\FlowConnector\Model\InventorySyncManager as Subject;
use FlowCommerce\FlowConnector\Model\Api\Inventory\Updates as InventoryUpdatesApiClient;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\Promise\Promise as HttpPromise;
use GuzzleHttp\Psr7\Response as HttpResponse;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use FlowCommerce\FlowConnector\Model\Api\Inventory\Updates\InventoryDataMapper;
use FlowCommerce\FlowConnector\Model\InventorySyncRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Class InventorySyncManagerTest
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @package FlowCommerce\FlowConnector\Test\Integration\Model
 */
class InventorySyncManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateProductsWithCategories
     */
    private $createProductsFixture;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var HttpClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpClient;

    /**
     * @var HttpClientFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpClientFactory;

    /**
     * @var HttpPromise|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpPromise;

    /**
     * @var HttpResponse|\PHPUnit_Framework_MockObject_MockObject
     */
    private $httpResponse;

    /**
     * @var InventoryUpdatesApiClient
     */
    private $itemUpdateApiClient;

    /**
     * @var Subject
     */
    private $subject;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var InventorySyncRepository
     */
    private $inventorySyncRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->httpResponse = $this->createPartialMock(HttpResponse::class, ['isSuccess']);
        $httpPromise = $this->objectManager->create(HttpPromise::class, [
            'waitFn' => function () use (&$httpPromise) {
                $httpPromise->resolve($this->httpResponse);
            }
        ]);
        $this->httpPromise = $httpPromise;
        $this->httpClient = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'postAsync'
            ])
            ->getMock();

        $this->httpClientFactory = $this->createConfiguredMock(
            HttpClientFactory::class,
            ['create' => $this->httpClient]
        );
        $this->itemUpdateApiClient = $this->objectManager->create(InventoryUpdatesApiClient::class, [
            'httpClientFactory' => $this->httpClientFactory,
        ]);
        $this->subject = $this->objectManager->create(Subject::class, [
            'itemUpdateApiClient' => $this->itemUpdateApiClient,
        ]);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->productRepository = $this->objectManager->create(ProductRepository::class);
        $this->inventorySyncRepository = $this->objectManager->create(InventorySyncRepository::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $this->auth = $this->objectManager->create(Auth::class);
        $this->urlBuilder = $this->objectManager->create(UrlBuilder::class, [
            'auth' => $this->auth
        ]);
        $this->storeManager = $this->objectManager->create(StoreManager::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/default_center_key center-1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/api_token 0123456789
     * @magentoConfigFixture current_store flowcommerce/flowconnector/organization_id 0123456789
     */
    public function testSyncsSuccessfullyWhenFlowModuleEnabled()
    {
        $this->createProductsFixture->execute();
        $products = $this->createProductsFixture->getProducts();

        $this->httpClient
            ->expects($this->any())
            ->method('postAsync')
            ->with($this->callback([$this, 'validateUrl']), $this->callback([$this, 'validateRequest']))
            ->willReturn($this->httpPromise);

        $this->subject->enqueueAllStockItems();

        $this->subject->process(100, 10);

        $searchCriteria = $this->searchCriteriaBuilder
            ->create();
        $inventorySyncs = $this->inventorySyncRepository->getList($searchCriteria);

        $this->assertEquals(
            $products->getTotalCount(),
            $inventorySyncs->getTotalCount(),
            'Failed asserting that all products are queued for inventory sync'
        );

        $productIds = [];
        foreach ($products->getItems() as $product) {
            $productIds[] = $product->getId();
        }

        /** @var InventorySync $inventorySync */
        foreach ($inventorySyncs->getItems() as $inventorySync) {
            $inventorySyncProductId = $inventorySync->getProductId();

            $this->assertEquals(InventorySync::STATUS_DONE, $inventorySync->getStatus());
            $this->assertEquals(1, $inventorySync->getStoreId());
            $this->assertContains($inventorySyncProductId, $productIds);

            if (($key = array_search($inventorySync->getProductId(), $productIds)) !== false) {
                unset($productIds[$key]);
            }
        }
        $this->assertCount(0, $productIds);
    }

    /**
     * Validates given raw body request against the respective product & stock item
     * @param $method
     * @param $url
     * @param $options
     * @return bool
     */
    public function validateRequest($options)
    {
        $rawBody = $options['body'];
        $auth = $options['auth'];
        $return = true;
        try {
            $this->assertEquals($this->auth->getAuthHeader($this->storeManager->getStore()->getId()), $auth);

            $jsonRequest = json_decode($rawBody);
            /** @var  $product */
            $product = $this->productRepository->get($jsonRequest->item_number);
            $stockItem = $product->getExtensionAttributes()->getStockItem();

            $this->assertEquals(
                'center-1',
                $jsonRequest->center,
                'Failed asserting that center matches'
            );

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(InventorySync::DATA_KEY_PRODUCT_ID, $product->getId(), 'eq')
                ->create();
            $inventorySyncs = $this->inventorySyncRepository->getList($searchCriteria);
            $this->assertEquals(1, $inventorySyncs->getTotalCount());
            $inventorySyncItems = $inventorySyncs->getItems();

            /** @var InventorySync $inventorySync */
            $inventorySync = reset($inventorySyncItems);
            $this->assertEquals(
                hash('sha256', $inventorySync->getId() . $inventorySync->getCreatedAt() . $inventorySync->getUpdatedAt()),
                $jsonRequest->idempotency_key,
                'Failed asserting that idempotency_key matches'
            );

            $this->assertEquals(
                $product->getSku(),
                $jsonRequest->item_number,
                'Failed asserting that item_number matches'
            );

            $this->assertEquals(
                $stockItem->getQty(),
                $jsonRequest->quantity,
                'Failed asserting that quantity matches'
            );

            $this->assertEquals(
                InventoryDataMapper::INVENTORY_UPDATE_TYPE_SET,
                $jsonRequest->type,
                'Failed asserting that type matches'
            );
        } catch (\Exception $e) {
            $return = false;
        }
        return $return;
    }

    /**
     * Validates given url
     * @param $url
     * @return bool
     */
    public function validateUrl($url)
    {
        $return = true;
        try {
            $this->assertEquals(
                $this->urlBuilder->getFlowApiEndpoint(InventoryUpdatesApiClient::URL_STUB_PREFIX),
                $url
            );
        } catch (Exception $e) {
            $return = false;
        }
        return $return;
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 0
     */
    public function testDoesNotEnqueueWhenFlowModuleDisabled()
    {
        $this->createProductsFixture->execute();
        $this->subject->enqueueAllStockItems();
        $searchCriteria = $this->searchCriteriaBuilder
            ->create();
        $inventorySyncs = $this->inventorySyncRepository->getList($searchCriteria);
        $this->assertEquals(0, $inventorySyncs->getTotalCount());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     */
    public function testEnqueuesWhenFlowModuleEnabled()
    {
        $this->createProductsFixture->execute();
        $products = $this->createProductsFixture->getProducts();
        $this->subject->enqueueAllStockItems();
        $searchCriteria = $this->searchCriteriaBuilder
            ->create();
        $inventorySyncs = $this->inventorySyncRepository->getList($searchCriteria);
        $this->assertEquals($products->getTotalCount(), $inventorySyncs->getTotalCount());
    }
}
