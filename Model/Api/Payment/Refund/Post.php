<?php

namespace FlowCommerce\FlowConnector\Model\Api\Payment\Refund;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

class Post
{
    /**
     * Url Stub of the record failures
     */
    const REFUNDS_URL_STUB = '/refunds';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

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

    /**
     * Delete constructor.
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Updates/creates webhooks in flow
     * @param int $storeId
     * @param string $key
     * @param string $value
     * @param string $reason
     * @param string[] $messages
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute($storeId, $flowPaymentRef, $localizedAmountToRefund, $localizedCurrencyCode)
    {
        $return = false;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::REFUNDS_URL_STUB, $storeId);
        $body = [
            'authorization_id' => $flowPaymentRef,
            'amount' => $localizedAmountToRefund,
            'currency' => $localizedCurrencyCode
        ];
        $payload = [
            'auth' => $this->auth->getAuthHeader($storeId),
            'body' => $this->jsonSerializer->serialize($body)
        ];

        try {
            $response = $client->post($url, $payload);
            $statusCode = (int) $response->getStatusCode();
            if ($statusCode === 201) {
                $this->logger->info('Order successfully refunded: ' . $response->getBody());
                $body = $this->jsonSerializer->unserialize($response->getBody());
                if (!$body || !isset($body['key'])) {
                    return false;
                }
                $return = $body['key'];
            } else {
                $this->logger->info('Order not successfully refunded: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Order not successfully refunded: ' . $e->getMessage());
        }

        return $return;
    }
}
