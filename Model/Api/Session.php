<?php

namespace FlowCommerce\FlowConnector\Model\Api;

use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as GuzzleHttpFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;
use GuzzleHttp\Client as GuzzleClient;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class Session
 * @package FlowCommerce\FlowConnector\Model\Api
 */
class Session
{
    /**
     * Session cookie duration
     */
    const COOKIE_DURATION = 86400;

    /**
     * URL stub for starting new session
     */
    const START_URL_STUB_PREFIX = '/sessions/organizations/';

    /**
     * URL stub for fetching session data
     */
    const GET_URL_STUB_PREFIX = '/sessions/';

    /**
     * Name of Flow session cookie
     */
    const FLOW_SESSION_COOKIE = '_f60_session';

    /**
     * Timeout for Flow http client
     */
    const FLOW_CLIENT_TIMEOUT = 30;

    /**
     * Number of seconds to delay before retrying
     */
    const FLOW_CLIENT_RETRY_DELAY = 30;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManagerInterface;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var GuzzleHttpFactory
     */
    private $guzzleHttpClientFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManagerInterface;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * Session constructor.
     * @param Auth $auth
     * @param CookieManagerInterface $cookieManagerInterface
     * @param GuzzleHttpFactory $guzzleHttpClientFactory
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param GuzzleClient $guzzleClient
     * @param StoreManager $storeManager
     * @param UrlBuilder $urlBuilder
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManagerInterface
     */
    public function __construct(
        Auth $auth,
        CookieManagerInterface $cookieManagerInterface,
        GuzzleHttpFactory $guzzleHttpClientFactory,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        GuzzleClient $guzzleClient,
        StoreManager $storeManager,
        UrlBuilder $urlBuilder,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManagerInterface
    ) {
        $this->auth = $auth;
        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->guzzleHttpClientFactory = $guzzleHttpClientFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->guzzleClient = $guzzleClient;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManagerInterface = $sessionManagerInterface;
    }

    /**
     * Returns array of current session data
     * @param $sessionId
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function getFlowSessionData($sessionId)
    {
        $url = $this->urlBuilder->getFlowApiEndpointWithoutOrganization(self::GET_URL_STUB_PREFIX) . $sessionId;
        $client = $this->guzzleHttpClientFactory->create();
        $response = $client->get($url);
        $contents = $this->jsonSerializer->unserialize($response->getBody());
        return $contents;
    }

    /**
     * Set flow session
     * @param null|string $country
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function startFlowSession($country = null)
    {
        $result = null;

        //Add country to body if set
        $body = [];
        if ($country) {
            $body = ['country' => $country];
        }
        $this->logger->info('Starting new Flow Session');

        /** @var GuzzleClient $client */
        $client = $this->guzzleHttpClientFactory->create();

        $url = $this->getFlowApiSessionEndpoint();

        $response = $client->post($url, [
            'auth' => $this->auth->getAuthHeader($this->getCurrentStoreId()),
            'body' => $this->jsonSerializer->serialize($body)
        ]);
        $result = $this->jsonSerializer->unserialize($response->getBody());

        return $result;
    }

    /**
     * Returns the Flow API Session endpoint with the specified url stub.
     * @return string
     * @throws NoSuchEntityException
     */
    private function getFlowApiSessionEndpoint()
    {
        return $this->urlBuilder->getFlowApiEndpointWithoutOrganization(self::START_URL_STUB_PREFIX)
            .$this->auth->getFlowOrganizationId($this->getCurrentStoreId());
    }

    /**
     * Returns the ID of the current store
     * @return int
     * @throws NoSuchEntityException
     */
    private function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
