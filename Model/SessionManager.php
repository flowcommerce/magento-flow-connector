<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\SessionManagementInterface;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use FlowCommerce\FlowConnector\Model\Api\Session as ApiSession;
use Magento\Quote\Model\Quote;

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
     * @var Configuration
     */
    private $configuration;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * SessionManager constructor.
     * @param CookieManagerInterface $cookieManagerInterface
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManagerInterface
     * @param ApiSession $sessionApiClient
     * @param Session $session
     * @param \FlowCommerce\FlowConnector\Model\Configuration $configuration
     * @param CustomerSession $customerSession
     * @param Session $checkoutSession
     * @param AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        CookieManagerInterface $cookieManagerInterface,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManagerInterface,
        ApiSession $sessionApiClient,
        Session $session,
        Configuration $configuration,
        CustomerSession $customerSession,
        Session $checkoutSession,
        AddressRepositoryInterface $addressRepository
    ) {

        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManagerInterface = $sessionManagerInterface;
        $this->sessionApiClient = $sessionApiClient;
        $this->session = $session;
        $this->configuration = $configuration;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->addressRepository = $addressRepository;
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
        if (!$flowSessionData) {
            $sessionId = $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE);
            if ($sessionId) {
                $flowSessionData = $this->sessionApiClient->getFlowSessionData($sessionId);
                if ($flowSessionData) {
                    $this->setFlowSessionData($flowSessionData);
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
        if ($flowSessionData && isset($flowSessionData['id']) && $flowSessionData['id']) {
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

    /**
     * Returns the full Flow checkout url with the specified items.
     * https://docs.flow.io/checkout/checkout
     * @param null $country
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCheckoutUrlWithCart($country = null)
    {
        $quote = $this->checkoutSession->getQuote();

        $items = $quote->getItems();
        if (!$items) {
            return null;
        }

        $url = $this->configuration->getFlowCheckoutUrl() . '?';

        // Additional custom attributes to pass through hosted checkout
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $quote->getId();

        $params = [];

        if ($country) {
            $params['country'] = $country;
        }

        if ($flowSessionId = $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE)) {
            $params['flow_session_id'] = $flowSessionId;
        }

        if ($customer = $this->customerSession->getCustomer()) {
            $attribs[WebhookEvent::CUSTOMER_ID] = $customer->getId();
            $attribs[WebhookEvent::CUSTOMER_SESSION_ID] = $this->customerSession->getSessionId();

            // Add customer info
            $params['customer[name][first]'] = $customer->getFirstname();
            $params['customer[name][last]'] = $customer->getLastname();
            $params['customer[number]'] = $customer->getId();
            $params['customer[phone]'] = $customer->getTelephone();
            $params['customer[email]'] = $customer->getEmail();

            // Add default shipping address
            if ($shippingAddressId = $customer->getDefaultShipping()) {
                $shippingAddress = $this->addressRepository->getById($shippingAddressId);

                $ctr = 0;
                foreach ($shippingAddress->getStreet() as $street) {
                    $params['destination[streets][' . $ctr . ']'] = $street;
                    $ctr += 1;
                }
                $params['destination[city]'] = $shippingAddress->getCity();
                $params['destination[province]'] = $shippingAddress->getRegion()->getRegionCode();
                $params['destination[postal]'] = $shippingAddress->getPostcode();
                $params['destination[country]'] = $shippingAddress->getCountryId();
                $params['destination[contact][name][first]'] = $shippingAddress->getFirstname();
                $params['destination[contact][name][last]'] = $shippingAddress->getLastname();
                $params['destination[contact][company]'] = $shippingAddress->getCompany();
                $params['destination[contact][email]'] = $customer->getEmail();
                $params['destination[contact][phone]'] = $shippingAddress->getTelephone();
            }
        }

        // Add cart items
        $ctr = 0;
        foreach ($items as $item) {
            $params['items[' . $ctr . '][number]'] = $item->getSku();
            $params['items[' . $ctr . '][quantity]'] = $item->getQty();
            $ctr += 1;
        }

        // Add custom attributes
        foreach ($attribs as $k => $v) {
            $params['attributes[' . $k . ']'] = $v;
        }

        $url = $url . http_build_query($params);
        return $url;
    }

    /**
     * Get session experience country
     * @return string|null $country
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSessionExperienceCountry()
    {
        $sessionData = $this->getFlowSessionData();
        $country = isset($sessionData['experience']['country']) ? $sessionData['experience']['country'] : null;
        return $country;
    }

    /**
     * Get session experience country
     * @return string|null $country
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSessionExperienceKey()
    {
        $sessionData = $this->getFlowSessionData();
        $country = isset($sessionData['experience']['key']) ? $sessionData['experience']['key'] : null;
        return $country;
    }

    /**
     * Set flow session data
     * @param $data
     */
    public function setFlowSessionData($data)
    {
        $this->session->setFlowSessionData($data);
    }
}