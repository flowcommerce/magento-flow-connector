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

class StreamRecordPut
{
    /**
     * Url Stub of the records streams
     */
    const RECORDS_STREAMS_URL_STUB = '/sync/records/streams';

    /**
     * Url Stub of the systems
     */
    const SYSTEMS_URL_STUB = '/systems';

    /**
     * Url Stub of the values
     */
    const VALUES_URL_STUB = '/values';

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
     * @param string $type
     * @param string $value
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute($storeId, $type, $value)
    {
        $return = false;

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(
            self::RECORDS_STREAMS_URL_STUB . '/' . $type . self::SYSTEMS_URL_STUB . '/m2' . self::VALUES_URL_STUB . '/' . $value,
            $storeId
        );

        $payload = [
            'auth' => $this->auth->getAuthHeader($storeId),
            'body' => $this->jsonSerializer->serialize($syncStreamForm)
        ];

        try {
            $response = $client->put($url);
            $statusCode = (int) $response->getStatusCode();
            if ($statusCode === 201 || $statusCode === 200) {
                $this->logger->info('Sync Stream Record registered: ' . $response->getBody());
                $return = true;
            } else {
                $this->logger->info('Sync Stream Record registration failed: ' . $response->getBody());
            }
        } catch (Exception $e) {
            $this->logger->info('Sync Stream Record registration failed: ' . $e->getMessage());
        }

        return $return;
    }
}
