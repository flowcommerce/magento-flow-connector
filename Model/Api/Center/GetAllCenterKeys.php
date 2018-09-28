<?php

namespace FlowCommerce\FlowConnector\Model\Api\Center;

use \FlowCommerce\FlowConnector\Model\Util;
use \GuzzleHttp\Client as HttpClient;
use \GuzzleHttp\ClientFactory as HttpClientFactory;
use \GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use \Psr\Log\LoggerInterface as Logger;

class GetAllCenterKeys
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/centers';

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * @var HttpRequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

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
     * @param HttpRequestFactory $httpRequestFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param Util $util
     */
    public function __construct(
        HttpClientFactory $httpClientFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        Util $util
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->util = $util;
    }

    /**
     * Deletes the sku from Flow.
     * @param int $storeId
     * @return string[]
     */
    public function execute($storeId)
    {
        $return = [];

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $apiToken = $this->util->getFlowApiToken($storeId);
        $urlStub = self::URL_STUB_PREFIX;
        $url = $this->util->getFlowApiEndpoint($urlStub, $storeId);

        $response = $client->get($url, ['auth' => [
            $apiToken,
            ''
        ]]);
        $centers = $this->jsonSerializer->unserialize($response->getBody()->getContents());
        foreach ($centers as $center) {
            if (array_key_exists('key', $center)) {
                array_push($return, (string) $center['key']);
                break;
            }
        }
        return $return;
    }
}
