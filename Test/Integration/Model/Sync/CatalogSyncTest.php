<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model\Sync;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\Collection as SyncSkuCollection;
use FlowCommerce\FlowConnector\Model\Sync\CatalogSync as Subject;
use FlowCommerce\FlowConnector\Model\Api\Item\Save as FlowSaveItemApi;
use FlowCommerce\FlowConnector\Model\SyncSku;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use GuzzleHttp\Client as GuzzleClient;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use \GuzzleHttp\Promise\Promise as HttpPromise;
use \GuzzleHttp\Psr7\Response as HttpResponse;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ProductMetadataInterface as ProductMetaData;
use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\TestFramework\Helper\Bootstrap;
use FlowCommerce\FlowConnector\Model\Configuration;

/**
 * Test class for \FlowCommerce\FlowConnector\Model\Sync\CatalogSync
 * @magentoAppIsolation enabled
 */
class CatalogSyncTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/catalog/items/';
    
    /**
     * @var CreateProductsWithCategories
     */
    private $createProductsFixture;

    /**
     * @var FlowSaveItemApi|\PHPUnit_Framework_MockObject_MockObject
     */
    private $flowSaveItemApi;

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
     * @var LocaleResolver
     */
    private $localeResolver;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductMetaData
     */
    private $productMetaData;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Subject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var StoreManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManager;

    /**
     * @var SyncSkuCollection
     */
    private $syncSkuCollection;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var Configuration
     */
    private $configuration;

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
                'putAsync'
            ])
            ->getMock();

        $this->httpClientFactory = $this->createConfiguredMock(
            HttpClientFactory::class,
            ['create' => $this->httpClient]
        );
        $this->flowSaveItemApi = $this->objectManager->create(FlowSaveItemApi::class, [
            'httpClientFactory' => $this->httpClientFactory,
        ]);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->productRepository = $this->objectManager->create(ProductRepository::class);
        $this->productMetaData = $this->objectManager->create(ProductMetaData::class);
        $this->localeResolver = $this->objectManager->create(LocaleResolver::class);
        $this->guzzleClient = $this->objectManager->create(GuzzleClient::class);
        $this->storeManager = $this->objectManager->create(StoreManager::class);
        $this->syncSkuCollection = $this->objectManager->create(SyncSkuCollection::class);
        $this->syncSkuManager = $this->objectManager->create(SyncSkuManager::class);
        $this->subject = $this->objectManager->create(Subject::class, [
            'flowSaveItemApi' => $this->flowSaveItemApi,
        ]);
        $this->urlBuilder = $this->objectManager->create(UrlBuilder::class);
        $this->configuration = $this->objectManager->create(Configuration::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     */
    public function testSyncsSuccessfullyWhenFlowModuleEnabled()
    {
        $this->createProductsFixture->execute();
        $products = $this->createProductsFixture->getProducts();

        $this->httpClient
            ->expects($this->any())
            ->method('putAsync')
            ->with($this->callback([$this, 'validateUrl']), $this->callback([$this, 'validateRequest']))
            ->willReturn($this->httpPromise);

        $this->syncSkuManager->enqueueAllProducts();
        $this->subject->process();

        $this->syncSkuCollection->load();
        $this->assertEquals($products->getTotalCount(), $this->syncSkuCollection->count());

        $productSkus = [];
        foreach ($products->getItems() as $product) {
            $productSkus[$product->getSku()] = $product->getSku();
        }
        $expectedSkus = ['simple_1', 'simple_2', 'simple_3', 'simple_4', 'configurable'];
        $this->assertEquals(
            sort($expectedSkus),
            sort(array_keys($productSkus)),
            'Failed to assert that the expected skus were generated'
        );

        /** @var SyncSku $syncSkuObject */
        foreach ($this->syncSkuCollection->getItems() as $syncSkuObject) {
            $syncSkuSku = $syncSkuObject->getSku();
            $product = $this->productRepository->get($syncSkuSku);
            if ($product->getTypeId() == ProductType::TYPE_SIMPLE) {
                $productOptions = $product->getOptions();
                $this->assertEquals(
                    1,
                    count($productOptions),
                    'Failed asserting that sku has one option: ' . $syncSkuSku
                );
                $expectedIsRequire = 0;
                if ($syncSkuSku == 'simple_4') {
                    $expectedIsRequire = 1;
                }
                $this->assertEquals(
                    $expectedIsRequire,
                    $productOptions[0]['is_require'],
                    'Failed asserting that sku has one required option: ' . $syncSkuSku
                );
            }

            $this->assertArrayHasKey($syncSkuSku, $productSkus);
            $this->assertEquals(1, $syncSkuObject->getStoreId());
            $this->assertEquals(SyncSku::STATUS_DONE, $syncSkuObject->getStatus(), 'Status not "done" for SKU: ' . $syncSkuSku);

            if (array_key_exists($syncSkuSku, $productSkus)) {
                unset($productSkus[$syncSkuSku]);
            }
        }
        $this->assertCount(0, $productSkus);
    }

    /**
     * Validates given raw body request against the respective product
     * @param $method
     * @param $url
     * @param $options
     * @return bool
     */
    public function validateRequest($options)
    {
        $rawBody = $options['body'];
        $return = true;
        try {
            $jsonRequest = json_decode($rawBody);
            $product = $this->productRepository->get($jsonRequest->number);
            $productSku = $product->getSku();
            $this->assertEquals(
                $productSku,
                $jsonRequest->number,
                'Failed asserting that name matches'
            );
            $this->assertEquals(
                $product->getName(),
                $jsonRequest->name,
                'Failed asserting that name matches'
            );
            $this->assertEquals(
                $this->localeResolver->getLocale(),
                $jsonRequest->locale,
                'Failed asserting that locale matches'
            );
            $this->assertEquals(
                $this->storeManager->getStore()->getCurrentCurrencyCode(),
                $jsonRequest->currency,
                'Failed asserting that currency matches'
            );
            $this->assertEquals(
                $product->getDescription(),
                $jsonRequest->description,
                'Failed asserting that description matches'
            );
            $this->assertEquals(
                $product->getAttributeText('status'),
                $jsonRequest->attributes->status,
                'Failed asserting that status matches'
            );
            $this->assertEquals(
                $product->getUrlKey(),
                $jsonRequest->attributes->url_key,
                'Failed asserting that url key matches'
            );
            $this->assertEquals(
                $product->getEntityId(),
                $jsonRequest->attributes->entity_id,
                'Failed asserting that entity id matches'
            );
            $this->assertEquals(
                $product->getTypeId(),
                $jsonRequest->attributes->type_id,
                'Failed asserting that type matches'
            );
            $this->assertEquals(
                $product->getAttributeSetId(),
                $jsonRequest->attributes->attribute_set_id,
                'Failed asserting that attribute set id matches'
            );
            $this->assertEquals(
                $product->getName(),
                $jsonRequest->attributes->name,
                'Failed asserting that attribute name matches'
            );
            $this->assertEquals(
                $product->getCreatedAt(),
                $jsonRequest->attributes->created_at,
                'Failed asserting that created at matches'
            );
            $this->assertEquals(
                $productSku,
                $jsonRequest->attributes->sku,
                'Failed asserting that sku matches'
            );
            $this->assertEquals(
                $product->getUpdatedAt(),
                $jsonRequest->attributes->updated_at,
                'Failed asserting that updated at matches'
            );
            $this->assertEquals(
                $product->getAttributeText('tax_class_id'),
                $jsonRequest->attributes->tax_class_id,
                'Failed asserting that tax_class_id matches'
            );
            $this->assertEquals(
                $product->getAttributeText('visibility'),
                $jsonRequest->attributes->visibility,
                'Failed asserting that visibility matches'
            );
            $this->assertEquals(
                $product->getAttributeText('country_of_manufacture'),
                $jsonRequest->attributes->country_of_manufacture,
                'Failed asserting that country_of_manufacture matches'
            );
            $this->assertEquals(
                $product->getAttributeText('shipment_type'),
                $jsonRequest->attributes->shipment_type,
                'Failed asserting that shipment_type matches'
            );
            $this->assertEquals(
                $product->getAttributeText('price_view'),
                $jsonRequest->attributes->price_view,
                'Failed asserting that price_view matches'
            );
            $this->assertEquals(
                $product->getAttributeText('msrp_display_actual_price_type'),
                $jsonRequest->attributes->msrp_display_actual_price_type,
                'Failed asserting that shipment_type matches'
            );
            $this->assertEquals(
                $product->getAttributeText('page_layout'),
                $jsonRequest->attributes->page_layout,
                'Failed asserting that shipment_type matches'
            );
            $this->assertEquals(
                $this->configuration->getFlowClientUserAgent(),
                $jsonRequest->attributes->user_agent,
                'Failed asserting that user_agent matches'
            );
            $this->assertEquals(
                $this->productMetaData->getVersion(),
                $jsonRequest->attributes->magento_version,
                'Failed asserting that magento_version matches'
            );

            if ($product->getTypeId() == ProductType::TYPE_SIMPLE) {
                $this->assertEquals(
                    $product->getPrice(),
                    $jsonRequest->price,
                    'Failed asserting that price matches'
                );
                $this->assertEquals(
                    $product->getWeight(),
                    $jsonRequest->dimensions->product->weight->value,
                    'Failed asserting that weight matches'
                );
                $this->assertEquals(
                    $product->getWeight(),
                    $jsonRequest->attributes->weight,
                    'Failed asserting that weight attribute matches'
                );
                if ($product->getPriceInfo()) {
                    foreach ($product->getPriceInfo()->getPrices() as $price) {
                        if ($price->getValue() &&
                            property_exists($jsonRequest->attributes, $price->getPriceCode())
                        ) {
                            $this->assertEquals(
                                (string)$price->getAmount(),
                                $jsonRequest->attributes->{$price->getPriceCode()},
                                'Failed asserting that ' . $price->getPriceCode() . ' matches'
                            );
                        }
                    }
                }
            } elseif ($product->getTypeId() == Configurable::TYPE_CODE) {
                $optionValues = ['Option 1', 'Option 2', 'Option 3', 'Option 4'];
                $optionLabels = ['Test Configurable'];
                $optionAttributeCodes = ['test_configurable'];
                $childrenProductSkus = ['simple_1', 'simple_2', 'simple_3', 'simple_4'];

                $this->assertEquals(
                    $optionValues,
                    json_decode($jsonRequest->attributes->children_attribute_test_configurable)
                );
                $this->assertEquals(
                    $optionLabels,
                    json_decode($jsonRequest->attributes->children_attribute_labels)
                );
                $this->assertEquals(
                    $optionAttributeCodes,
                    json_decode($jsonRequest->attributes->children_attribute_codes)
                );
                $this->assertEquals(
                    $childrenProductSkus,
                    json_decode($jsonRequest->attributes->children_product_skus)
                );
            }

            $this->assertCount(2, $jsonRequest->images);
            foreach ($jsonRequest->images as $image) {
                $this->assertRegExp('/^http.*\.jpg/', $image->url);
                $this->assertContains($image->tags[0], ['thumbnail', 'checkout']);
            }

            $categories = ['Category 1 > Category 2', 'Category 3 > Category 4', 'Category 5'];
            $this->assertEquals($categories, $jsonRequest->categories);
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
            $endpoint = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX);
            $this->assertContains($endpoint, $url);
            $sku = substr($url, strrpos($url, '/') + 1);//get last string
            $this->assertTrue($this->isValidSku($sku));
        } catch (Exception $e) {
            $return = false;
        }
        return $return;
    }
    
    /**
     * Check if sku is valid
     * @param $sku
     * @return bool
     */
    public function isValidSku($sku)
    {
        $return = true;
        try {
            $this->productRepository->get($sku);
        } catch (Exception $e) {
            $return = false;
        }
        return $return;
    }
}
