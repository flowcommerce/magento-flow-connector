<?php

namespace FlowCommerce\FlowConnector\Model\Api\Webhook;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

class Put
{
    /**
     * Url Stub of this API endpoint
     */
    const URL_STUB = '/sync/streams';

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
     * Delete constructor.
     * @param Auth $auth
     * @param HttpClientFactory $httpClientFactory
     * @param HttpRequestFactory $httpRequestFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Auth $auth,
        HttpClientFactory $httpClientFactory,
        HttpRequestFactory $httpRequestFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        UrlBuilder $urlBuilder
    ) {
        $this->auth = $auth;
        $this->httpClientFactory = $httpClientFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Updates/creates webhooks in flow
     * @param int $storeId
     * @param string $type
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute($storeId, $type)
    {
        $return = false;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB, $storeId);

        $syncStreamForm = [
            'type' => $type,
            'systems' => ['flow','m2'],
        ];
        $payload = [
            'auth' => $this->auth->getAuthHeader($storeId),
            'body' => $this->jsonSerializer->serialize($syncStreamForm)
        ];

        try {
            $response = $client->put($url . '/' . $type, $payload);
            $statusCode = (int) $response->getStatusCode();
            if ($statusCode === 201 || $statusCode === 200) {
                $this->logger->info('Sync Stream registered: ' . $response->getBody());
                $return = true;
            } else {
                $this->logger->info('Sync Stream registration failed: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Sync Stream registration failed: ' . $e->getMessage());
        }

        return $return;
    }
}
