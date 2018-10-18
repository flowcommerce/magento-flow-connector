<?php

namespace FlowCommerce\FlowConnector\Model\Api\Item;

use \FlowCommerce\FlowConnector\Model\SyncSku;
use \FlowCommerce\FlowConnector\Model\Util;
use \FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as HttpClient;
use \FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use \GuzzleHttp\PoolFactory as HttpPoolFactory;
use \GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use \Psr\Log\LoggerInterface as Logger;

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
     * @var Util|null
     */
    private $util = null;

    /**
     * Delete constructor.
     * @param HttpClientFactory $httpClientFactory
     * @param HttpPoolFactory $httpPoolFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param Logger $logger
     * @param Util $util
     */
    public function __construct(
        HttpClientFactory $httpClientFactory,
        HttpPoolFactory $httpPoolFactory,
        HttpRequestFactory $httpRequestFactory,
        Logger $logger,
        Util $util
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->httpPoolFactory = $httpPoolFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->logger = $logger;
        $this->util = $util;
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
                $apiToken = $this->util->getFlowApiToken($syncSku->getStoreId());
                $urlStub = self::URL_STUB_PREFIX . rawurlencode($syncSku->getSku());
                $url = $this->util->getFlowApiEndpoint($urlStub, $syncSku->getStoreId());
                yield function () use ($client, $url, $apiToken) {
                    return $client->deleteAsync($url, ['auth' => [
                        $apiToken,
                        ''
                    ]]);
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
