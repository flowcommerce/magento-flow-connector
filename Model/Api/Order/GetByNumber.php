<?php

namespace FlowCommerce\FlowConnector\Model\Api\Order;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface as Logger;

class GetByNumber
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/orders/';

    /**
     * @var Auth
     */
    private $auth;

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
     * @var UrlBuilder
     */
    private $urlBuilder;

    /** @var StoreManager */
    private $storeManager;

    /**
     * Get constructor.
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     * @param StoreManager $storeManager
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        UrlBuilder $urlBuilder,
        StoreManager $storeManager
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve orders
     * @param $storeId
     * @param $number
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute($storeId = null, $number = null)
    {
        $return = [];
        if (!$storeId) {
            $storeId = $this->getCurrentStoreId();
        }
        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId);
        $response = $client->get(
            $url . $number,
            [
                'auth' => $this->auth->getAuthHeader($storeId),
            ]
        );

        $result = (string) $response->getBody();
        if ($result) {
            $return = $this->jsonSerializer->unserialize($result);
        }
        return $return;
    }

    /**
     * Returns the ID of the current store
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
