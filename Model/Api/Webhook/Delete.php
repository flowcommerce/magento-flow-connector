<?php

namespace FlowCommerce\FlowConnector\Model\Api\Webhook;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Util;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use GuzzleHttp\Psr7\RequestFactory as HttpRequestFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

class Delete
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/webhooks';

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

    /**
     * @var Util|null
     */
    private $util = null;

    /**
     * Delete constructor.
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     * @param Util $util
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        UrlBuilder $urlBuilder,
        Util $util
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
        $this->util = $util;
    }

    /**
     * Retrieves all webhooks registered with flow
     * @param int $storeId
     * @param string $webhookId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute($storeId, $webhookId)
    {
        $return = false;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId) . '/' . $webhookId;

        $response = $client->delete($url, ['auth' => $this->auth->getAuthHeader($storeId)]);

        if ((int) $response->getStatusCode() === 204) {
            $this->logger->info('Webhook ' . $webhookId . ' deleted.');
            $return = true;
        } else {
            $this->logger->info('Webhook deletion failed: ' . $response->getBody());
        }

        return $return;
    }
}
