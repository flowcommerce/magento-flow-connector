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
use Magento\Store\Model\StoreManager;
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
     * @param string $sessionId
     * @return array|bool
     * @throws NoSuchEntityException
     */
    public function execute($body, $query = [], $sessionId = null)
    {
        $storeId = $this->getCurrentStoreId();

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create(['options' => [
            'headers' => [
                'Authorization' => 'Session ' . $sessionId
            ]
        ]]);
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId);

        try {
            $this->logger->info('Order create attempt: ' . json_encode($body));
            $response = $client->post($url, [
                'auth' => $this->auth->getAuthHeader($storeId),
                'body' => $this->jsonSerializer->serialize($body),
                'query' => $query
            ]);

            if ((int) $response->getStatusCode() === 201) {
                $this->logger->info('Order created: ' . $response->getBody());
                $return = (string) $response->getBody();
            } else {
                throw new Exception(sprintf('Status code %s: %s', $response->getStatusCode(), $response->getBody()));
            }
        } catch (Exception $e) {
            $this->logger->info(sprintf('Order creation failed due to: %s', $e->getMessage()));

            throw $e;
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

    /**
     * Create secure checkout redirect token
     * @param $orderForm
     * @param string $sessionId
     * @param $customer
     * @param $addressBook
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function createCheckoutToken($orderForm, $sessionId, $customer, $addressBook)
    {
        $storeId = $this->getCurrentStoreId();
        $result = null;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create(['options' => [
            'headers' => [
                'Authorization' => 'Session ' . $sessionId
            ]
        ]]);
        $url = $this->urlBuilder->getFlowApiEndpoint('/checkout/tokens', $storeId);
        $body = (object)[
            'order_form' => $orderForm,
            'session_id' => $sessionId,
            'customer' => $customer,
            'address_book' => $addressBook,
            'discriminator' => 'checkout_token_order_form',
            'urls' => (object)[
                /* "continue_shopping" => $this->storeManager->getStore()->getBaseUrl() */
            ]
        ];

        try {
            $response = $client->post($url, [
                'auth' => $this->auth->getAuthHeader($storeId),
                'body' => $this->jsonSerializer->serialize($body),
            ]);

            if ((int) $response->getStatusCode() === 201) {
                $this->logger->info('Checkout token created: ' . $response->getBody());
                $body = (string) $response->getBody();
                $tokenResponse = json_decode($body);
                if (isset($tokenResponse->id)) {
                    $result = $tokenResponse->id;
                } 
            } else {
                $this->logger->info(sprintf('Status code %s: %s', $response->getStatusCode(), $response->getBody()));
            }
        } catch (Exception $e) {
            $this->logger->info(sprintf('Checkout token creation failed due to: %s', $e->getMessage()));

            throw $e;
        }

        return $result;
    }

    /**
     * Update existing order
     * @param string $flowOrderNumber
     * @param $body
     * @param array $query
     * @param string $sessionId
     * @return array|bool
     * @throws NoSuchEntityException
     */
    public function update($flowOrderNumber, $body, $query, $sessionId)
    {
        $storeId = $this->getCurrentStoreId();

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create(['options' => [
            'headers' => [
                'Authorization' => 'Session ' . $sessionId
            ]
        ]]);
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX . '/' . $flowOrderNumber, $storeId);

        try {
            $this->logger->info('Order update attempt: ' . json_encode($body));
            $response = $client->put($url, [
                'auth' => $this->auth->getAuthHeader($storeId),
                'body' => $this->jsonSerializer->serialize($body),
                'query' => $query
            ]);

            if ((int) $response->getStatusCode() === 200) {
                $this->logger->info('Order updated: ' . $response->getBody());
                $return = (string) $response->getBody();
            } else {
                throw new Exception(sprintf('Status code %s: %s', $response->getStatusCode(), $response->getBody()));
            }
        } catch (Exception $e) {
            $this->logger->info(sprintf('Order update failed due to: %s', $e->getMessage()));

            throw $e;
        }

        return $return;
    }
}
