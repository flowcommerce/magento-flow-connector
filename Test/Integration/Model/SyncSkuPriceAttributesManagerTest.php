<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Api\Attribute\Save as AttributeApiClientSave;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use FlowCommerce\FlowConnector\Model\SyncSkuPriceAttributesManager as Subject;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SyncSkuPriceAttributesManager extends TestCase
{
    const ORGANIZATION_ID = 'organization-id';
    const API_TOKEN = 'api-token';
    const STORE_ID = 1;

    /**
     * @var AttributeApiClientSave
     */
    private $attributeSaveApiClient;

    /**
     * @var string[]
     */
    private $attributes = [];

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HttpClient
     */
    private $httpClientSave;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactorySave;

    /**
     * @var HttpResponse
     */
    private $httpResponseSave;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Subject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->auth = $this->objectManager->create(Auth::class);
        $this->urlBuilder = $this->objectManager->create(UrlBuilder::class);

        $this->httpResponseSave = $this->createPartialMock(HttpResponse::class, [
            'getStatusCode'
        ]);
        $this->httpClientSave = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'request'
            ])
            ->getMock();
        $this->httpClientFactorySave = $this->createConfiguredMock(
            HttpClientFactory::class,
            ['create' => $this->httpClientSave]
        );
        $this->attributeSaveApiClient = $this->objectManager->create(AttributeApiClientSave::class, [
            'httpClientFactory' => $this->httpClientFactorySave,
        ]);

        $this->storeManager = $this->objectManager->create(StoreManager::class);

        $this->jsonSerializer = $this->objectManager->create(Json::class);

        $this->subject = $this->objectManager->create(Subject::class, [
            'attributeApiClientSave' => $this->attributeSaveApiClient
        ]);

        foreach (Subject::PRICE_ATTRIBUTE_CODES as $priceAttributeCode) {
            $this->attributes[$priceAttributeCode] = $priceAttributeCode;
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 0
     */
    public function testWebhookRegistrationIsNotExecutedWhenModuleDisabled()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->httpClientSave
                ->expects($this->never())
                ->method('request');
            $this->subject->createPriceAttributesInFlow($store->getId());
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/organization_id organization-id
     * @magentoConfigFixture current_store flowcommerce/flowconnector/api_token api-token
     * @throws NoSuchEntityException
     */
    public function testSuccessfullyExecutesWhenModuleEnabled()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->httpResponseSave
                ->expects($this->exactly(count(Subject::PRICE_ATTRIBUTE_CODES)))
                ->method('getStatusCode')
                ->willReturn(201);
            $this->httpClientSave
                ->expects($this->exactly(count(Subject::PRICE_ATTRIBUTE_CODES)))
                ->method('request')
                ->with(
                    $this->equalTo('put'),
                    $this->callback([$this, 'validateAttributeSaveUrl']),
                    $this->callback([$this, 'validateAttributeSaveRequest'])
                )
                ->willReturn($this->httpResponseSave);

            $this->subject->createPriceAttributesInFlow($store->getId());

            $this->assertEmpty($this->attributes, 'Not all of the available attributes were saved');
        }
    }

    /**
     * @param string $url
     * @return bool
     */
    public function validateAttributeSaveUrl($url)
    {
        $explodedUrl = explode('/', $url);
        $key = array_pop($explodedUrl);
        $this->assertContains($key, $this->attributes);
        return true;
    }

    /**
     * @param string[][] $request
     * @return bool
     * @throws NoSuchEntityException
     */
    public function validateAttributeSaveRequest($request)
    {
        $this->assertArrayHasKey('auth', $request);
        $this->assertEquals($this->auth->getAuthHeader(self::STORE_ID), $request['auth']);
        $this->assertArrayHasKey('body', $request);

        $attribute = $this->jsonSerializer->unserialize($request['body']);
        $this->assertArrayHasKey('key', $attribute);
        $this->assertArrayHasKey('intent', $attribute);
        $this->assertArrayHasKey('type', $attribute);
        $this->assertArrayHasKey('options', $attribute);

        $options = $attribute['options'];
        $this->assertArrayHasKey('required', $options);
        $this->assertArrayHasKey('show_in_catalog', $options);
        $this->assertArrayHasKey('show_in_harmonization', $options);

        foreach ($this->attributes as $attributekey) {
            if (preg_match('/' . $attributekey . '/i', $attribute['key'])) {
                $this->assertEquals(AttributeApiClientSave::INTENT_PRICE, $attribute['intent']);
                $this->assertEquals(AttributeApiClientSave::TYPE_DECIMAL, $attribute['type']);
                $this->assertFalse($options['required']);
                $this->assertFalse($options['show_in_catalog']);
                $this->assertFalse($options['show_in_harmonization']);
                unset($this->attributes[$attributekey]);
                break;
            }
        }
        return true;
    }
}
