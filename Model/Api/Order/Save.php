<?php

namespace FlowCommerce\FlowConnector\Model\Api\Order;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Test\Block\Adminhtml\Order\Create\Store;
use Magento\Store\Model\StoreManager;
use Magento\TestFramework\TestCase\Webapi\Adapter\Soap;
use Psr\Log\LoggerInterface as Logger;

class Save
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/orders';

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
    private $logger;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * Delete constructor.
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
     * Create an order, using the localized information from the experience selected by the query parameters.
     * Note the order must be submitted before its expiration
     * @param $body
     * @param array $query
     * @return array|bool
     * @throws NoSuchEntityException
     */
    public function execute($body, $query = [])
    {
        $return = [];
        $storeId = $this->getCurrentStoreId();

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId);

        try {
            $response = $client->post($url, [
                'auth' => $this->auth->getAuthHeader($storeId),
                'body' => $this->jsonSerializer->serialize($body),
                'query' => $query
            ]);

            if ((int) $response->getStatusCode() === 201) {
                $this->logger->info('Order created: ' . $response->getBody());
                $return = (string) $response->getBody();
            } else {
                $this->logger->info('Order creation failed: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Order creation failed: ' . $e->getMessage());
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
