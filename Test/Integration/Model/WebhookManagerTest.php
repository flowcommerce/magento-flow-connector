<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Delete as WebhookApiClientDelete;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Get as WebhookApiClientGet;
use FlowCommerce\FlowConnector\Model\Api\Webhook\Save as WebhookApiClientSave;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use FlowCommerce\FlowConnector\Model\WebhookManager as Subject;
use FlowCommerce\FlowConnector\Model\WebhookManager\EndpointsConfiguration as WebhookEndpointsConfig;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class WebhookManagerTest
 * @package FlowCommerce\FlowConnector\Test\Integration\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class WebhookManagerTest extends TestCase
{
    const ORGANIZATION_ID = 'organization-id';
    const API_TOKEN = 'api-token';
    const BASE_URL = 'https://dev.flow.io/flowconnector/webhooks/';
    const STORE_ID = 1;
    const NUM_EXISTING_WEBHOOKS = 10;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HttpClient
     */
    private $httpClientDelete;

    /**
     * @var HttpClient
     */
    private $httpClientGet;

    /**
     * @var HttpClient
     */
    private $httpClientSave;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactoryDelete;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactoryGet;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactorySave;

    /**
     * @var HttpResponse
     */
    private $httpResponseDelete;

    /**
     * @var HttpResponse
     */
    private $httpResponseGet;

    /**
     * @var HttpResponse
     */
    private $httpResponseSave;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var string[][]
     */
    private $getWebhooksMockedResponse;

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
     * @var string[][]
     */
    private $stubsVsEvents;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var WebhookApiClientDelete
     */
    private $webhookDeleteApiClient;

    /**
     * @var WebhookApiClientGet
     */
    private $webhookGetApiClient;

    /**
     * @var WebhookApiClientSave
     */
    private $webhookSaveApiClient;

    /**
     * @var WebhookEndpointsConfig
     */
    private $webhookEndpointsConfig;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->auth = $this->objectManager->create(Auth::class);
        $this->urlBuilder = $this->objectManager->create(UrlBuilder::class);

        $this->httpResponseDelete = $this->createPartialMock(HttpResponse::class, [
            'getStatusCode'
        ]);
        $this->httpClientDelete = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'request'
            ])
            ->getMock();
        $this->httpClientFactoryDelete = $this->createConfiguredMock(
            HttpClientFactory::class,
            ['create' => $this->httpClientDelete]
        );
        $this->webhookDeleteApiClient = $this->objectManager->create(WebhookApiClientDelete::class, [
            'httpClientFactory' => $this->httpClientFactoryDelete,
        ]);

        $this->httpResponseGet = $this->createPartialMock(HttpResponse::class, [
            'getStatusCode',
            'getBody'
        ]);
        $this->httpClientGet = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'request'
            ])
            ->getMock();
        $this->httpClientFactoryGet = $this->createConfiguredMock(
            HttpClientFactory::class,
            ['create' => $this->httpClientGet]
        );
        $this->webhookGetApiClient = $this->objectManager->create(WebhookApiClientGet::class, [
            'httpClientFactory' => $this->httpClientFactoryGet,
        ]);
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
        $this->webhookSaveApiClient = $this->objectManager->create(WebhookApiClientSave::class, [
            'httpClientFactory' => $this->httpClientFactorySave,
        ]);

        $this->storeManager = $this->objectManager->create(StoreManager::class);

        $this->webhookEndpointsConfig = $this->objectManager->create(WebhookEndpointsConfig::class);
        $this->stubsVsEvents = $this->webhookEndpointsConfig->getEndpointsConfiguration();
        $this->jsonSerializer = $this->objectManager->create(Json::class);

        $this->subject = $this->objectManager->create(Subject::class, [
            'webhookDeleteApiClient' => $this->webhookDeleteApiClient,
            'webhookGetApiClient' => $this->webhookGetApiClient,
            'webhookSaveApiClient' => $this->webhookSaveApiClient,
        ]);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 0
     */
    public function testWebhookRegistrationIsNotExecutedWhenModuleDisabled()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->httpClientGet
                ->expects($this->never())
                ->method('request');
            $this->httpClientDelete
                ->expects($this->never())
                ->method('request');
            $this->httpClientSave
                ->expects($this->never())
                ->method('request');
            $this->expectException(Exception::class);
            $this->subject->registerAllWebhooks($store->getId());
        }
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/organization_id organization-id
     * @magentoConfigFixture current_store flowcommerce/flowconnector/api_token api-token
     * @throws NoSuchEntityException
     */
    public function testWebhookRegistrationSuccessfullyExecutesWhenModuleEnabled()
    {
        foreach ($this->storeManager->getStores() as $store) {
            $getResponseBody = $this->objectManager->create(DataObject::class);
            $getResponseBody->setContents($this->getWebhooksMockResponse());
            $this->httpResponseGet
                ->expects($this->once())
                ->method('getBody')
                ->willReturn($getResponseBody);
            $this->httpClientGet
                ->expects($this->once())
                ->method('request')
                ->with(
                    $this->equalTo('get'),
                    $this->equalTo($this->urlBuilder->getFlowApiEndpoint(WebhookApiClientGet::URL_STUB_PREFIX)),
                    $this->equalto(['auth' => $this->auth->getAuthHeader(self::STORE_ID)])
                )
                ->willReturn($this->httpResponseGet);

            $this->httpResponseDelete
                ->expects($this->exactly(count($this->webhookEndpointsConfig->getEndpointsConfiguration())))
                ->method('getStatusCode')
                ->willReturn(204);
            $this->httpClientDelete
                ->expects($this->exactly(count($this->webhookEndpointsConfig->getEndpointsConfiguration())))
                ->method('request')
                ->with(
                    $this->equalTo('delete'),
                    $this->callback([$this, 'validateWebhookDeleteUrl']),
                    $this->equalto(['auth' => $this->auth->getAuthHeader(self::STORE_ID)])
                )
                ->willReturn($this->httpResponseDelete);

            $this->httpResponseSave
                ->expects($this->exactly(count($this->webhookEndpointsConfig->getEndpointsConfiguration())))
                ->method('getStatusCode')
                ->willReturn(204);
            $this->httpClientSave
                ->expects($this->exactly(count($this->webhookEndpointsConfig->getEndpointsConfiguration())))
                ->method('request')
                ->with(
                    $this->equalTo('post'),
                    $this->equalTo($this->urlBuilder->getFlowApiEndpoint(WebhookApiClientGet::URL_STUB_PREFIX)),
                    $this->callback([$this, 'validateWebhookSaveRequest'])
                )
                ->willReturn($this->httpResponseSave);

            $this->subject->registerAllWebhooks($store->getId());

            $this->assertEmpty($this->stubsVsEvents, 'Not all of the available webhooks were registered');
        }
    }

    /**
     * Mocks a response for the webhooks get all request
     * @return string
     */
    private function getWebhooksMockResponse()
    {
        $baseUrl = self::BASE_URL . self::ORGANIZATION_ID . '/';
        $urlSuffix = '?storeId=' . self::STORE_ID;

        $this->getWebhooksMockedResponse = [];
        foreach ($this->webhookEndpointsConfig->getEndpointsConfiguration() as $stub => $events) {
            $webhook = [
                'id' => 'whk-' . $stub,
                'url' => $baseUrl . $stub . $urlSuffix,
                'events' => $events,
            ];
            array_push($this->getWebhooksMockedResponse, $webhook);
        }
        return $this->jsonSerializer->serialize($this->getWebhooksMockedResponse);
    }

    /**
     * @param string $url
     * @return bool
     */
    public function validateWebhookDeleteUrl($url)
    {
        $ids = array_column($this->getWebhooksMockedResponse, 'id');
        $explodedUrl = explode('/', $url);
        $id = array_pop($explodedUrl);
        $this->assertContains($id, $ids);
        $idIndex = array_search($id, $ids);
        unset($this->getWebhooksMockedResponse[$idIndex]);
        $this->getWebhooksMockedResponse = array_values($this->getWebhooksMockedResponse);
        return true;
    }

    /**
     * @param string[][] $request
     * @return bool
     * @throws NoSuchEntityException
     */
    public function validateWebhookSaveRequest($request)
    {
        $this->assertArrayHasKey('auth', $request);
        $this->assertEquals($this->auth->getAuthHeader(self::STORE_ID), $request['auth']);
        $this->assertArrayHasKey('body', $request);

        $webhook = $this->jsonSerializer->unserialize($request['body']);
        $this->assertArrayHasKey('url', $webhook);
        $this->assertArrayHasKey('events', $webhook);
        foreach ($this->stubsVsEvents as $stub => $events) {
            if (preg_match('/' . $stub . '/i', $webhook['url'])) {
                $this->assertEquals($events, $webhook['events']);
                unset($this->stubsVsEvents[$stub]);
                break;
            }
        }
        return true;
    }
}
