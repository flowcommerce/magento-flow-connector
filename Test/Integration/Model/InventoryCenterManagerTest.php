<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Api\Center\GetAllCenterKeys as InventoryCenterApiGet;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use FlowCommerce\FlowConnector\Model\InventoryCenterManager as Subject;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class InventoryCenterManagerTest
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @package FlowCommerce\FlowConnector\Test\Integration\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryCenterManagerTest extends TestCase
{
    const INVENTORY_CENTER_KEY = 'test-inventory-key';
    const STORE_ID = 1;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var HttpClient
     */
    private $httpClientGet;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactoryGet;

    /**
     * @var HttpResponse
     */
    private $httpResponseGet;

    /**
     * @var InventoryCenterApiGet
     */
    private $inventoryCenterApiGet;

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

        $this->httpResponseGet = $this->createPartialMock(HttpResponse::class, [
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
        $this->inventoryCenterApiGet = $this->objectManager->create(InventoryCenterApiGet::class, [
            'httpClientFactory' => $this->httpClientFactoryGet,
        ]);

        $this->storeManager = $this->objectManager->create(StoreManager::class);

        $this->jsonSerializer = $this->objectManager->create(Json::class);

        $this->dataObjectFactory = $this->objectManager->create(DataObjectFactory::class);

        $this->subject = $this->objectManager->create(Subject::class, [
            'flowCentersApiClient' => $this->inventoryCenterApiGet
        ]);
    }

    /**
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 0
     */
    public function testWebhookRegistrationIsNotExecutedWhenModuleDisabled()
    {
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            array_push($storeIds, $store->getId());
        }
        $this->httpClientGet
            ->expects($this->never())
            ->method('request');
            $this->subject->fetchInventoryCenterKeys($storeIds);
    }

    /**
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/organization_id organization-id
     * @magentoConfigFixture current_store flowcommerce/flowconnector/api_token api-token
     * @throws NoSuchEntityException
     */
    public function testSuccessfullyExecutesWhenModuleEnabled()
    {
        $getResponse = $this->dataObjectFactory->create();
        $getResponse->setData('contents', $this->getInventoryCentersMockResponse());
        $this->httpResponseGet
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($getResponse);
        $this->httpClientGet
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('get'),
                $this->equalTo($this->urlBuilder->getFlowApiEndpoint(InventoryCenterApiGet::URL_STUB_PREFIX)),
                $this->equalto(['auth' => $this->auth->getAuthHeader(self::STORE_ID)])
            )
            ->willReturn($this->httpResponseGet);

        $this->subject->fetchInventoryCenterKeys();

        foreach ($this->storeManager->getStores() as $store) {
            $this->assertEquals(
                self::INVENTORY_CENTER_KEY,
                $this->subject->getDefaultCenterKeyForStore($store->getId())
            );
        }
    }

    /**
     * Mocks a response for the inventory centers get request
     * @return string
     */
    private function getInventoryCentersMockResponse()
    {
        $inventoryCentersResponse = [
            [
                'key' => self::INVENTORY_CENTER_KEY,
                'name' => 'Inventory Center'
            ]
        ];
        return $this->jsonSerializer->serialize($inventoryCentersResponse);
    }
}
