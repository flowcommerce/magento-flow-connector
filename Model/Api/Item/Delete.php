<?php

namespace FlowCommerce\FlowConnector\Model\Api\Item;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\SyncSku;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\PoolFactory as HttpPoolFactory;
use GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Delete
 * @package FlowCommerce\FlowConnector\Model\Api
 */
class Delete
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
     * @var Logger|null
     */
    private $logger = null;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Delete constructor.
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param HttpPoolFactory $httpPoolFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     * @param Util $util
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        HttpPoolFactory $httpPoolFactory,
        HttpRequestFactory $httpRequestFactory,
        Logger $logger,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpPoolFactory = $httpPoolFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Deletes the sku from Flow.
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
                $storeId = $syncSku->getStoreId();
                $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX . rawurlencode($syncSku->getSku()), $storeId);
                yield function () use ($client, $url, $storeId) {
                    return $client->deleteAsync($url, ['auth' => $this->auth->getAuthHeader($storeId)]);
                };
                $syncSku->setData('flow_request_url', $url);
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
