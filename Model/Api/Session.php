<?php

namespace FlowCommerce\FlowConnector\Model\Api;

use FlowCommerce\FlowConnector\Model\GuzzleHttp\ClientFactory as GuzzleHttpFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;
use FlowCommerce\FlowConnector\Model\GuzzleHttp\Client as GuzzleClient;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Request\Http as Request;

/**
 * Class Session
 * @package FlowCommerce\FlowConnector\Model\Api
 */
class Session
{
    /**
     * Cookie duration
     */
    const COOKIE_DURATION = 86400;

    /**
     * Sessions url
     */
    const URL_STUB_PREFIX = 'sessions/';

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
     * @var Request
     */
    private $request;

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
     * @param Request $request
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
        SessionManagerInterface $sessionManagerInterface,
        Request $request

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
        $this->request = $request;
    }

    /**
     * Returns array of current session data
     */
    public function getFlowSessionData()
    {
        $sessionId = $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE);
        $contents = null;
        if ($sessionId) {
            $url = $this->getFlowApiSessionEndpoint() . $sessionId;
            $client = $this->guzzleHttpClientFactory->create();
            $response = $client->get($url);
            $contents = $this->jsonSerializer->unserialize($response->getBody());
        }
        return $contents;
    }

    /**
     * Set flow session
     * @param null $country
     */
    public function setFlowSessionData($country = null)
    {
        //Add country to body if set
        $body = [];
        if ($country) {
            $body = ['country' => $country];
        }
        $this->logger->info('Setting Flow Session');

        /** @var GuzzleClient $client */
        $client = $this->guzzleHttpClientFactory->create();

        try {
            $url = $this->getFlowApiSessionEndpoint() . 'organizations/' .
                $this->auth->getFlowOrganizationId($this->getCurrentStoreId());

            $response = $client->post($url, [
                'auth' => $this->auth->getAuthHeader($this->getCurrentStoreId()),
                'body' => $this->jsonSerializer->serialize($body)
            ]);
            $responseBody = $this->jsonSerializer->unserialize($response->getBody());
            $sessionId = $responseBody['id'];
            if ($sessionId && $sessionId !== '') {
                $cookieMeta = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setDuration(self::COOKIE_DURATION)
                    ->setPath('/')
                    ->setDomain($this->sessionManagerInterface->getCookieDomain())
                    ->setHttpOnly(false);

                $this->cookieManagerInterface->setPublicCookie(
                    self::FLOW_SESSION_COOKIE,
                    $sessionId,
                    $cookieMeta
                );
            }
        } catch (LocalizedException $exception) {
            $this->logger->info('Flow session not created');
        }
    }

    /**
     * Returns the Flow API Session endpoint with the specified url stub.
     * @return string
     */
    private function getFlowApiSessionEndpoint()
    {
        return UrlBuilder::FLOW_API_BASE_ENDPOINT . self::URL_STUB_PREFIX;
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