<?php

namespace FlowCommerce\FlowConnector\Model\Api\Experience;

use FlowCommerce\FlowConnector\Model\Api\Auth;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use GuzzleHttp\Client as HttpClient;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as HttpClientFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

class GetAllExperiences
{
    /**
     * Url Stub Prefix of this API endpoint
     */
    const URL_STUB_PREFIX = '/experiences';

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
     * Gets all enabled experiences from Flow.
     * @param int $storeId
     * @return []
     * @throws NoSuchEntityException
     */
    public function execute($storeId)
    {
        $return = [];

        /** @var HttpClient $client */
        $client = $this->httpClientFactory->create();
        $url = $this->urlBuilder->getFlowApiEndpoint(self::URL_STUB_PREFIX, $storeId);
        $response = $client->get($url, ['auth' => $this->auth->getAuthHeader($storeId)]);

        $experiences = $this->jsonSerializer->unserialize($response->getBody()->getContents());
        foreach ($experiences as $experience) {
            if (!isset($experience['status']) ||
                !isset($experience['key']) ||
                !isset($experience['currency']) ||
                !isset($experience['country'])
            ) {
                continue;
            }
            if ($experience['status'] != 'active') {
                continue;
            }
            $return[] = $experience;
        }
        return $return;
    }
}
