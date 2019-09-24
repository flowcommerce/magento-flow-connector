<?php

namespace FlowCommerce\FlowConnector\Model\Api\Inventory;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Api\Inventory\Updates\InventoryDataMapper;
use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface as InventorySync;
use FlowCommerce\FlowConnector\Api\InventorySyncRepositoryInterface as InventorySyncRepository;
use GuzzleHttp\Client as HttpClient;
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
     * @var InventorySyncRepository
     */
    private $inventorySyncRepository;

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
     * @param InventorySyncRepository $inventorySyncRepository
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
        InventorySyncRepository $inventorySyncRepository,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->jsonSerializer = $jsonSerializer;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpPoolFactory = $httpPoolFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->logger = $logger;
        $this->inventoryDataMapper = $inventoryDataMapper;
        $this->inventorySyncRepository = $inventorySyncRepository;
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
                $storeId = $inventorySync->getStoreId();
                if (!$inventorySync->getProduct()) {
                    $this->logger->warning(
                        'Product id: ' .
                        $inventorySync->getProductId() .
                        ' does not have an associated product for store id: ' .
                        $storeId .
                        ' and will not be synced.'
                    );
                    $this->markInventorySyncAsError($inventorySync, $e->getMessage());
                    continue;
                }
                if (!$inventorySync->getStockItem()) {
                    $this->logger->warning(
                        'Product Id: ' .
                        $inventorySync->getProductId() .
                        ' does not have stock information available for store id: ' .
                        $storeId .
                        ' and will not be synced.'
                    );
                    $this->markInventorySyncAsError($inventorySync, $e->getMessage());
                    continue;
                }
                try {
                    $ts = microtime(true);
                    $inventoryApiRequest = $this->inventoryDataMapper->map($inventorySync);
                    $this->logger->info('Time to convert stock item to flow data: ' . (microtime(true) - $ts));
                    $serializedInventoryApiRequest = $this->jsonSerializer->serialize($inventoryApiRequest);
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

    // Duplicated from Model\InventorySyncManager.php due to circular dependency and class scoping problem.
    // TODO refactor hotfix
    /**
     * {@inheritdoc}
     */
    public function markInventorySyncAsError(InventorySync $inventorySync, $errorMessage = null)
    {
        $inventorySync->setStatus(InventorySync::STATUS_ERROR);
        $inventorySync->setMessage($errorMessage);
        $this->inventorySyncRepository->save($inventorySync);
    }
}
