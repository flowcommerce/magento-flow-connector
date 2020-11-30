<?php

namespace FlowCommerce\FlowConnector\Model\Api\Sync\Stream\Pending\Record;

use Exception;
use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

class GetByKey
{
    /**
     * Url Stub of this API endpoint
     */
    const URL_STUB = '/sync/pending/records';

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
     * Gets sync stream pending records by key
     * @param int $storeId
     * @param string $key
     * @return []
     * @throws NoSuchEntityException
     */
    public function execute($storeId, $key)
    {
        $return = [];

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB, $storeId);

        $payload = [ 'auth' => $this->auth->getAuthHeader($storeId) ];

        try {
            $response = $client->get($url . '?stream_key=' . $key, $payload);
            $statusCode = (int) $response->getStatusCode();
            $this->logger->info('Sync Stream get pending records by key response code: ' . $statusCode);
            if ($statusCode === 200) {
                $result = (string) $response->getBody();
                if ($result) {
                    $return = $this->jsonSerializer->unserialize($result);
                }
            } else {
                $this->logger->info('Sync Stream get pending records by key failed: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Sync Stream get pending records by key failed: ' . $e->getMessage());
        }

        return $return;
    }
}
