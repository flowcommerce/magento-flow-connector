<?php

namespace FlowCommerce\FlowConnector\Model\Api\Inventory;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Api\Inventory\Updates\InventoryDataMapper;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\PoolFactory as HttpPoolFactory;
use GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

/**
 * Inventory Updates Api async http client
 * @package FlowCommerce\FlowConnector\Model\Api\Inventory
 */
class Updates
{
    /**
     * Number of concurrent requests to be sent to Flow's API
     */
    const ASYNC_REQUESTS_CONCURRENCY = 10;

    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/inventory_updates';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var HttpPoolFactory
     */
    private $httpPoolFactory;

    /**
     * @var HttpRequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var InventoryDataMapper
     */
    private $inventoryDataMapper;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Save constructor.
     * @param Auth $auth
     * @param JsonSerializer $jsonSerializer
     * @param HttpClientFactory $httpClientFactory
     * @param HttpPoolFactory $httpPoolFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param Logger $logger
     * @param InventoryDataMapper $inventoryDataMapper
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        HttpPoolFactory $httpPoolFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        InventoryDataMapper $inventoryDataMapper,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->jsonSerializer = $jsonSerializer;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpPoolFactory = $httpPoolFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->logger = $logger;
        $this->inventoryDataMapper = $inventoryDataMapper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Syncs the specified product's stock item to the Flow inventory.
     * @param InventorySync[] $inventorySyncs
     * @param callable $successCallback
     * @param callable $failureCallback
     */
    public function execute($inventorySyncs, $successCallback, $failureCallback)
    {
        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $requests = function ($inventorySyncs) use ($client) {
            /** @var InventorySync $inventorySync */
            foreach ($inventorySyncs as $inventorySync) {
                try {
                    $ts = microtime(true);
                    $inventoryApiRequest = $this->inventoryDataMapper->map($inventorySync);
                    $this->logger->info('Time to convert stock item to flow data: ' . (microtime(true) - $ts));
                    $serializedInventoryApiRequest = $this->jsonSerializer->serialize($inventoryApiRequest);
                    $storeId = $inventorySync->getStoreId();
                    $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId);
                    yield function () use ($client, $url, $storeId, $serializedInventoryApiRequest) {
                        return $client->postAsync($url, [
                            'body' => $serializedInventoryApiRequest,
                            'auth' => $this->auth->getAuthHeader($storeId)
                        ]);
                    };
                } catch (\Exception $e) {
                    $this->logger->warning('Error syncing inventory: '
                        . $e->getMessage() . '\n' . $e->getTraceAsString());
                    $this->markInventorySyncAsError($inventorySync, $e->getMessage());
                }
            }
        };

        $pool = $this->httpPoolFactory->create([
            'client' => $client,
            'requests' => $requests($inventorySyncs),
            'config' => [
                'concurrency' => self::ASYNC_REQUESTS_CONCURRENCY,
                'fulfilled' => $successCallback,
                'rejected' => $failureCallback,
            ],
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
    }
}
