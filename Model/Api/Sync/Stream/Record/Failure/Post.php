<?php

namespace FlowCommerce\FlowConnector\Model\Api\Sync\Stream\Record\Failure;

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
    const RECORD_FAILURES_URL_STUB = '/sync/record/failures';

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
    public function execute($storeId, $key, $value, $reason, $messages)
    {
        $return = false;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::RECORD_FAILURES_URL_STUB, $storeId);
        $body = [
            'stream_key' => $key,
            'value' => $value,
            'system' => 'm2',
            'reason' => $reason,
            'attributes' => [
                'messages' => implode(',', $messages)
            ]
        ];
        $payload = [
            'auth' => $this->auth->getAuthHeader($storeId),
            'body' => $this->jsonSerializer->serialize($body)
        ];

        try {
            $response = $client->post($url, $payload);
            $statusCode = (int) $response->getStatusCode();
            if ($statusCode === 201) {
                $this->logger->info('Sync Stream Record Failure registered: ' . $response->getBody());
                $return = true;
            } else {
                $this->logger->info('Sync Stream Record Failure registration failed: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Sync Stream Record Failure registration failed: ' . $e->getMessage());
        }

        return $return;
    }
}
