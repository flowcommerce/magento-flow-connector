<?php

namespace FlowCommerce\FlowConnector\Model\Api\Inventory;

use \FlowCommerce\FlowConnector\Exception\InventorySyncException;
use \FlowCommerce\FlowConnector\Model\Api\Inventory\Updates\InventoryDataMapper;
use \FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use \FlowCommerce\FlowConnector\Model\Util;
use \GuzzleHttp\Client as HttpClient;
use \GuzzleHttp\ClientFactory as HttpClientFactory;
use \GuzzleHttp\PoolFactory as HttpPoolFactory;
use \GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use \Psr\Log\LoggerInterface as Logger;

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
     * @var Util
     */
    private $util;

    /**
     * Save constructor.
     * @param JsonSerializer $jsonSerializer
     * @param HttpClientFactory $httpClientFactory
     * @param HttpPoolFactory $httpPoolFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param Logger $logger
     * @param InventoryDataMapper $inventoryDataMapper
     * @param Util $util
     */
    public function __construct(
        HttpClientFactory $httpClientFactory,
        HttpPoolFactory $httpPoolFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        InventoryDataMapper $inventoryDataMapper,
        Util $util
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpPoolFactory = $httpPoolFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->logger = $logger;
        $this->inventoryDataMapper = $inventoryDataMapper;
        $this->util = $util;
    }

    /**
     * Syncs the specified product's stock item to the Flow inventory.
     * @param InventorySync[] $inventorySyncs
     * @param callable $successCallback
     * @param callable $failureCallback
     * @throws InventorySyncException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($inventorySyncs, $successCallback, $failureCallback)
    {
        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $requests = function ($inventorySyncs) use ($client) {
            /** @var InventorySync $inventorySync */
            foreach ($inventorySyncs as $inventorySync) {
                $stockItem = $inventorySync->getStockItem();
                $ts = microtime(true);
                $inventoryApiRequest = $this->inventoryDataMapper->map($inventorySync, $stockItem);
                $this->logger->info('Time to convert stock item to flow data: ' . (microtime(true) - $ts));
                $serializedInventoryApiRequest = $this->jsonSerializer->serialize($inventoryApiRequest);
                $apiToken = $this->util->getFlowApiToken($inventorySync->getStoreId());
                $url = $this->util->getFlowApiEndpoint(self::URL_STUB_PREFIX, $inventorySync->getStoreId());
                yield function () use ($client, $url, $apiToken, $serializedInventoryApiRequest) {
                    return $client->postAsync($url, ['body' => $serializedInventoryApiRequest, 'auth' => [
                        $apiToken,
                        ''
                    ]]);
                };
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
