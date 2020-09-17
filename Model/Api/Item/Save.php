<?php

namespace FlowCommerce\FlowConnector\Model\Api\Item;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Api\Item\Save\ProductDataMapper;
use FlowCommerce\FlowConnector\Model\SyncSku;
use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\PoolFactory as HttpPoolFactory;
use GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

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
     * @var ProductDataMapper
     */
    private $productDataMapper;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

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
     * @param SyncSkuManager $syncSkuManager
     * @param ProductDataMapper $productDataMapper
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        HttpPoolFactory $httpPoolFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        SyncSkuManager $syncSkuManager,
        ProductDataMapper $productDataMapper,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->jsonSerializer = $jsonSerializer;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpPoolFactory = $httpPoolFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
        $this->productDataMapper = $productDataMapper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Syncs the specified product to the Flow catalog.
     * @param SyncSku[] $syncSkus
     * @param callable $successCallback
     * @param callable $failureCallback
     */
    public function execute($syncSkus, $successCallback, $failureCallback)
    {
        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $requests = function ($syncSkus) use ($client) {
            /** @var SyncSku $syncSku */
            foreach ($syncSkus as $syncSku) {
                try {
                    $product = $syncSku->getProduct();
                    $data = $this->productDataMapper->map($syncSku, $product);
                    $urls = [];
                    foreach ($data as $item) {
                        $url = $this->urlBuilder->getFlowApiEndpoint(
                            self::URL_STUB_PREFIX . rawurlencode($item['number']),
                            $syncSku->getStoreId()
                        );
                        $storeId = $syncSku->getStoreId();
                        array_push($urls, $url);
                        $serializedItem = $this->jsonSerializer->serialize($item);
                        yield function () use ($client, $url, $serializedItem, $storeId) {
                            return $client->putAsync($url, [
                                'body' => $serializedItem,
                                'auth' => $this->auth->getAuthHeader($storeId)
                            ]);
                        };
                    }
                    $syncSku->setData('flow_request_body', $this->jsonSerializer->serialize($data));
                    $syncSku->setData('flow_request_url', $this->jsonSerializer->serialize($urls));
                } catch (\Exception $e) {
                    $this->logger->warning('Error syncing product ' . $syncSku->getSku() . ': '
                        . $e->getMessage() . '\n' . $e->getTraceAsString());
                    $this->syncSkuManager->markSyncSkuAsError($syncSku, $e->getMessage());
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
