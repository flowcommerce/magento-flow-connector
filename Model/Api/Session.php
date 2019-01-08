<?php

namespace FlowCommerce\FlowConnector\Model\Api;

use GuzzleHttp\Client as GuzzleHttpFactory;
use \Magento\Framework\Stdlib\CookieManagerInterface;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Class Session
 * @package FlowCommerce\FlowConnector\Model\Api
 */
class Session
{
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
     * @var CookieManagerInterface
     */
    private $cookieManagerInterface;

    /**
     * @var GuzzleHttpFactory
     */
    private $guzzleHttpClientFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * Session constructor.
     * @param CookieManagerInterface $cookieManagerInterface
     * @param GuzzleHttpFactory $guzzleHttpClientFactory
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        CookieManagerInterface $cookieManagerInterface,
        GuzzleHttpFactory $guzzleHttpClientFactory,
        JsonSerializer $jsonSerializer
    ) {
        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->guzzleHttpClientFactory = $guzzleHttpClientFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Returns array of current session data
     */
    public function getFlowSessionData()
    {
        $sessionId = $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE);
        $url = UrlBuilder::FLOW_API_BASE_ENDPOINT . '/sessions/' . $sessionId;
        $client = $this->guzzleHttpClientFactory->create();
        $response = $client->get($url);
        $contents = $this->jsonSerializer->unserialize($response->getBody()->getContents());
        return $contents;
    }

}