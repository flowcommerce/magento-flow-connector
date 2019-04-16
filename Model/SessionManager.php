<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\SessionManagementInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use FlowCommerce\FlowConnector\Model\Api\Session as ApiSession;
use Magento\Quote\Model\Quote;
use FlowCommerce\FlowConnector\Model\Api\Order\Get as OrderGet;
use Psr\Log\LoggerInterface;

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
     * Name of Flow session cookie update flag
     */
    const FLOW_SESSION_UPDATE_COOKIE_FLAG = 'flow_mage_session_update';

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
     * @var Configuration
     */
    private $configuration;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var FlowCartManager
     */
    private $flowCartManager;

    /*
     * @var OrderGet
     */
    private $orderGet;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SessionManager constructor.
     * @param CookieManagerInterface $cookieManagerInterface
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param SessionManagerInterface $sessionManagerInterface
     * @param ApiSession $sessionApiClient
     * @param \FlowCommerce\FlowConnector\Model\Configuration $configuration
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param AddressRepositoryInterface $addressRepository
     * @param OrderGet $orderGet
     * @param LoggerInterface $logger
     */
    public function __construct(
        CookieManagerInterface $cookieManagerInterface,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManagerInterface,
        ApiSession $sessionApiClient,
        Configuration $configuration,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        AddressRepositoryInterface $addressRepository,
        OrderGet $orderGet,
        LoggerInterface $logger
    ) {

        $this->cookieManagerInterface = $cookieManagerInterface;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManagerInterface = $sessionManagerInterface;
        $this->sessionApiClient = $sessionApiClient;
        $this->configuration = $configuration;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->addressRepository = $addressRepository;
        $this->orderGet = $orderGet;
        $this->logger = $logger;
    }

    /**
     * Retrieve data for Flow session in progress
     * @return array|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function getFlowSessionData()
    {
        $flowSessionData = $this->checkoutSession->getFlowSessionData();
        if (!$flowSessionData || $this->isUpdateSession()) {
            $sessionId = $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE);
            if ($sessionId) {
                $flowSessionData = $this->sessionApiClient->getFlowSessionData($sessionId);
                if ($flowSessionData) {
                    $this->setFlowSessionData($flowSessionData);
                    $this->cookieManagerInterface->deleteCookie(self::FLOW_SESSION_UPDATE_COOKIE_FLAG);
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

            $this->checkoutSession->setFlowSessionData($flowSessionData);
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

        $items = $quote->getAllVisibleItems();
        if (!$items || !$country) {
            return null;
        }


        // Additional custom attributes to pass through hosted checkout
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $quote->getId();

        $params = [];

        if ($country) {
            $params['country'] = $country;
        }

        // Removed, unnecessary for now
        /* $flowOrderNumber = $quote->getFlowConnectorOrderNumber(); */
        /* $query = ['number' => $flowOrderNumber]; */
        /* $result = $this->orderGet->execute($query); */
        /* $flowOrderData = reset($result); */
        
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

                // Using strpos since country codes coming from FlowJS will not match M2 country codes directly but will likely be contained within them
                if (strpos($country, $shippingAddress->getCountryId()) !== FALSE) {
                    $ctr = 0;
                    foreach ($shippingAddress->getStreet() as $street) {
                        $params['destination[streets][' . $ctr . ']'] = $street;
                        $ctr += 1;
                    }
                    $params['destination[city]'] = $shippingAddress->getCity();
                    $params['destination[province]'] = $shippingAddress->getRegion()->getRegionCode();
                    $params['destination[postal]'] = $shippingAddress->getPostcode();
                    $params['destination[country]'] = $country;
                    $params['destination[contact][name][first]'] = $shippingAddress->getFirstname();
                    $params['destination[contact][name][last]'] = $shippingAddress->getLastname();
                    $params['destination[contact][company]'] = $shippingAddress->getCompany();
                    $params['destination[contact][email]'] = $customer->getEmail();
                    $params['destination[contact][phone]'] = $shippingAddress->getTelephone();
                }
            }
        }

        // Add cart items
        $ctr = 0;
        foreach ($items as $item) {
            $params['items[' . $ctr . '][number]'] = $item->getSku();
            $itemRowTotal = $item->getRowTotal();
            $itemDiscountAmount = $item->getDiscountAmount();
            $itemDiscountPercentage = 0.0;
            if ($itemRowTotal > 0 && $itemDiscountAmount > 0) {
                $itemDiscountPercentage = (float)(($itemDiscountAmount / $itemRowTotal) * 100);
            }

            $params['items[' . $ctr . '][quantity]'] = $item->getQty();
            if ($itemDiscountPercentage > 0) {
                $params['items[' . $ctr . '][discounts][discounts][0][offer][discriminator]'] = 'discount_offer_percent';
                $params['items[' . $ctr . '][discounts][discounts][0][offer][percent]'] = $itemDiscountPercentage;
            }
            $ctr += 1;
        }

        // Add custom attributes
        foreach ($attribs as $k => $v) {
            $params['attributes[' . $k . ']'] = $v;
        }

        $this->logger->info('CART: ' . json_encode($params));
        $this->logger->info('REDIRECT: ' . $this->configuration->getFlowCheckoutUrl() . '?' . http_build_query($params));
        return $this->configuration->getFlowCheckoutUrl() . '?' . http_build_query($params);
    }

    /**
     * Get session experience country
     * @return string|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function getSessionExperienceCountry()
    {
        $sessionData = $this->getFlowSessionData();
        $country = isset($sessionData['experience']['country']) ? $sessionData['experience']['country'] : null;
        return $country;
    }

    /**
     * Get session experience country
     * @return string|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function getSessionExperienceKey()
    {
        $sessionData = $this->getFlowSessionData();
        if ($country = isset($sessionData['experience']['key']) ? $sessionData['experience']['key'] : null) {
            $this->logger->info('Current Experience Key: '.$country);
        }
        return $country;
    }

    /**
     * Set flow session data
     * @param $data
     */
    public function setFlowSessionData($data)
    {
        $this->checkoutSession->setFlowSessionData($data);
    }

    /**
     * Return cookie flag if session is to be updated
     * @return bool
     */
    private function isUpdateSession()
    {
        return (bool)$this->cookieManagerInterface->getCookie(self::FLOW_SESSION_UPDATE_COOKIE_FLAG);
    }
}
