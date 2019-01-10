<?php

namespace FlowCommerce\FlowConnector\Model\Api\Webhook;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Util;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

class Settings
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/webhook/settings';

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
     * Settings constructor.
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     * @param Util $util

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
     * Updates webhook settings
     *
     * @param $storeId
     * @param $secret
     * @param int $retryMaxAttempts
     * @param int $retrySleepMs
     * @param int $sleepMs
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute($storeId, $secret, $retryMaxAttempts, $retrySleepMs, $sleepMs)
    {
        $return = false;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId);

        $body = [
            'secret' => $secret,
            'retry_max_attempts' => $retryMaxAttempts,
            'retry_sleep_ms' =>  $retrySleepMs,
            'sleep_ms' => $sleepMs
        ];

        try {
            $response = $client->put($url, [
                'auth' => $this->auth->getAuthHeader($storeId),
                'body' => $this->jsonSerializer->serialize($body)
            ]);

            if ((int) $response->getStatusCode() === 200) {
                $this->logger->info('Webhook settings updated: ' . $response->getBody());
                $return = true;
            } else {
                $this->logger->info('Webhook settings update failed: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Webhook settings update failed: ' . $e->getMessage());
        }

        return $return;
    }
}
