<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\Util;

/**
 * Test class for CatalogSync.
 */
class CatalogSyncTest extends \PHPUnit\Framework\TestCase {

    const SCOPE_CONFIG_VALUE_MAP = [
        Util::FLOW_ENABLED => true,
        Util::FLOW_ORGANIZATION_ID => 'test-organization',
        Util::FLOW_API_TOKEN => 'abcdefghijklmnopqrstuvwxyz'
    ];

    protected $logger;
    protected $jsonHelper;
    protected $util;
    protected $storeManager;
    protected $store;
    protected $objectManager;
    protected $linkManagement;
    protected $categoryCollectionFactory;
    protected $blockFactory;
    protected $appEmulation;
    protected $imageFactory;
    protected $productCollectionFactory;
    protected $mediaGallery;
    protected $localResolver;
    protected $scopeConfig;
    protected $configurable;
    protected $productFactory;
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $imageBuilder;
    protected $eventManager;
    protected $syncSkuFactory;
    protected $syncSku;
    protected $syncSkuCollection;
    protected $productAttribute;
    protected $imageBlock;
    protected $image;
    protected $client;
    protected $response;
    protected $dateTime;
    protected $countryFactory;
    protected $productMetaData;

    protected function setUp() {
        $this->scopeConfig = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->scopeConfig->method('getValue')
            ->will($this->returnCallback(function($key) {
                return self::SCOPE_CONFIG_VALUE_MAP[$key];
            }));

        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);

        $this->response = $this->createMock(\Zend\Http\Response::class);
        $this->response->method('isSuccess')->willReturn(true);

        $this->client = $this->createMock(\Zend\Http\Client::class);

        $this->util = $this->createMock(\FlowCommerce\FlowConnector\Model\Util::class);
        $this->util->method('isFlowEnabled')->willReturn(true);
        $this->util->method('getFlowClient')->willReturn($this->client);
        $this->util->method('sendFlowClient')->willReturn($this->response);
        $this->util->expects($this->once())->method('sendFlowClient');

        $this->store = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->storeManager->method('getStore')->willReturn($this->store);

        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->linkManagement = $this->createMock(\Magento\ConfigurableProduct\Api\LinkManagementInterface::class); $this->categoryCollectionFactory = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);

        $this->image = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->imageBlock = $this->createMock(\Magento\Catalog\Block\Product\ListProduct::class);
        $this->imageBlock->method('getImage')->willReturn($this->image);

        $this->blockFactory = $this->createMock(\Magento\Framework\View\Element\BlockFactory::class);
        $this->blockFactory->method('createBlock')->willReturn($this->imageBlock);

        $this->appEmulation = $this->createMock(\Magento\Store\Model\App\Emulation::class);
        $this->imageFactory = $this->createMock(\Magento\Catalog\Model\Product\ImageFactory::class);
        $this->productCollectionFactory = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);
        $this->mediaGallery = $this->createMock(\Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface::class);
        $this->localeResolver = $this->createMock(\Magento\Framework\Locale\Resolver::class);
        $this->configurable = $this->createMock(\Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable::class);
        $this->productFactory = $this->createMock(\Magento\Catalog\Model\ProductFactory::class);
        $this->productRepository = $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->imageBuilder = $this->createMock(\Magento\Catalog\Block\Product\ImageBuilder::class);
        $this->eventManager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);

        $this->syncSkuCollection = $this->createMock(\FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\Collection::class);
        $this->syncSkuCollection->method('addFieldToFilter')->will($this->returnSelf());
        $this->syncSkuCollection->method('setPageSize')->will($this->returnSelf());

        $this->syncSku = $this->createMock(\FlowCommerce\FlowConnector\Model\SyncSku::class);
        $this->syncSku->method('getCollection')->willReturn($this->syncSkuCollection);
        $this->syncSku->expects($this->once())->method('save');

        $this->syncSkuFactory = $this->createMock(\FlowCommerce\FlowConnector\Model\SyncSkuFactory::class);
        $this->syncSkuFactory->method('create')->willReturn($this->syncSku);

        $this->dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->countryFactory = $this->createMock(\Magento\Directory\Model\CountryFactory::class);
        $this->productMetaData = $this->createMock(\Magento\Framework\App\ProductMetadataInterface::class);

        $this->catalogSync = new \FlowCommerce\FlowConnector\Model\Sync\CatalogSync(
            $this->logger,
            $this->jsonHelper,
            $this->util,
            $this->storeManager,
            $this->objectManager,
            $this->linkManagement,
            $this->categoryCollectionFactory,
            $this->blockFactory,
            $this->appEmulation,
            $this->imageFactory,
            $this->productCollectionFactory,
            $this->mediaGallery,
            $this->localeResolver,
            $this->scopeConfig,
            $this->configurable,
            $this->productFactory,
            $this->productRepository,
            $this->searchCriteriaBuilder,
            $this->imageBuilder,
            $this->eventManager,
            $this->dateTime,
            $this->countryFactory,
            $this->syncSkuFactory,
            $this->productMetaData
        );

        $this->productAttribute = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);
    }

    /**
     * Test CatalogSync queue and syncProduct.
     */
    public function testQueueAndSync() {
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getAttributes')
            ->willReturn([$this->productAttribute]);

        $this->catalogSync->queue($product);
        $this->catalogSync->syncProduct($this->syncSku, $product);
    }

}
