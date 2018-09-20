<?php

namespace FlowCommerce\FlowConnector\Model\Api\Item;

use \FlowCommerce\FlowConnector\Exception\CatalogSyncException;
use \FlowCommerce\FlowConnector\Model\Api\Item\Save\ProductDataMapper;
use \FlowCommerce\FlowConnector\Model\SyncSku;
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
 * Class Save
 * @package FlowCommerce\FlowConnector\Model\Api
 */
class Save
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/catalog/items/';

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
     * @var ProductDataMapper
     */
    private $productDataMapper;

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
     * @param ProductDataMapper $productDataMapper
     * @param Util $util
     */
    public function __construct(
        HttpClientFactory $httpClientFactory,
        HttpPoolFactory $httpPoolFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        ProductDataMapper $productDataMapper,
        Util $util
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpPoolFactory = $httpPoolFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->logger = $logger;
        $this->productDataMapper = $productDataMapper;
        $this->util = $util;
    }

    /**
     * Syncs the specified product to the Flow catalog.
     * @param SyncSku[] $syncSkus
     * @param callable $successCallback
     * @param callable $failureCallback
     * @throws CatalogSyncException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($syncSkus, $successCallback, $failureCallback)
    {
        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $requests = function ($syncSkus) use ($client) {
            /** @var SyncSku $syncSku */
            foreach ($syncSkus as $syncSku) {
                $product = $syncSku->getProduct();
                $ts = microtime(true);
                $data = $this->productDataMapper->map($syncSku, $product);
                $this->logger->info('Time to convert product to flow data: ' . (microtime(true) - $ts));
                $apiToken = $this->util->getFlowApiToken($syncSku->getStoreId());
                foreach ($data as $item) {
                    $urlStub = self::URL_STUB_PREFIX . urlencode($item['number']);
                    $url = $this->util->getFlowApiEndpoint($urlStub, $syncSku->getStoreId());
                    $serializedItem = $this->jsonSerializer->serialize($item);
                    yield function () use ($client, $url, $apiToken, $serializedItem) {
                        return $client->putAsync($url, ['body' => $serializedItem, 'auth' => [
                            $apiToken,
                            ''
                        ]]);
                    };
                }
            }
        };

        $pool = $this->httpPoolFactory->create([
            'client' => $client,
            'requests' => $requests($syncSkus),
            'config' => [
                'concurrency' => 10,
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
