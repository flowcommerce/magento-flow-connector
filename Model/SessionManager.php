<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\SessionManagementInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use FlowCommerce\FlowConnector\Model\Api\Session as ApiSession;

class SessionManager implements SessionManagementInterface
{
    /**
     * Cookie duration
     */
    const COOKIE_DURATION = 86400;

    /**
     * Name of Flow session cookie
     */
    const FLOW_SESSION_COOKIE = '_f60_session';

    /**
     * @var SessionManagerInterface
     */
    private $sessionManagerInterface;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManagerInterface;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var ApiSession
     */
    private $sessionApiClient;

    /**
     * @var Session
     */
    private $session;

    /**
     * SessionManager constructor.
     * @param CookieManagerInterface $cookieManagerInterface
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManagerInterface
     * @param ApiSession $sessionApiClient
     * @param Session $session
     */
    public function __construct(
        CookieManagerInterface $cookieManagerInterface,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManagerInterface,
        ApiSession $sessionApiClient,
        Session $session
    ) {

        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManagerInterface = $sessionManagerInterface;
        $this->sessionApiClient = $sessionApiClient;
        $this->session = $session;
    }

    /**
     * Retrieve data for Flow session in progress
     *
     * @return array|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowSessionData()
    {
        $flowSessionData = $this->session->getFlowSessionData();
        if(!$flowSessionData) {
            $sessionId = $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE);
            if ($sessionId) {
                $flowSessionData = $this->sessionApiClient->getFlowSessionData($sessionId);
                if($flowSessionData) {
                    $this->session->setFlowSessionData($flowSessionData);
                }
            }
        }

        return $flowSessionData;
    }

    /**
     * Start new Flow session
     *
     * @param $country
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function startFlowSession($country)
    {
        $flowSessionData = $this->sessionApiClient->startFlowSession($country);
        if($flowSessionData && isset($flowSessionData['id']) && $flowSessionData['id']) {
            $cookieMeta = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(self::COOKIE_DURATION)
                ->setPath('/')
                ->setDomain($this->sessionManagerInterface->getCookieDomain())
                ->setHttpOnly(false);

            $this->cookieManagerInterface->setPublicCookie(
                self::FLOW_SESSION_COOKIE,
                $flowSessionData['id'],
                $cookieMeta
            );

            $this->session->setFlowSessionData($flowSessionData);
        }
    }
}