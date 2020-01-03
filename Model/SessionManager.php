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
use FlowCommerce\FlowConnector\Model\Api\Order\Save as OrderSave;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class SessionManager implements SessionManagementInterface
{
    /**
     * Session cookie duration
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
     * Label for buy request info
     */
    const INFO_BUYREQUEST_LABEL = 'info_buyRequest';

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

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /*
     * @var OrderGet
     */
    private $orderGet;

    /*
     * @var OrderSave
     */
    private $orderSave;

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
     * @param StoreManagerInterface $storeManager
     * @param OrderGet $orderGet
     * @param OrderSave $orderSave
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
        StoreManagerInterface $storeManager,
        OrderGet $orderGet,
        OrderSave $orderSave,
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
        $this->storeManager = $storeManager;
        $this->orderGet = $orderGet;
        $this->orderSave = $orderSave;
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
    public function getCheckoutUrlWithCart($country = null, $currency = null)
    {
        $quote = $this->checkoutSession->getQuote();
        $result = null;

        $items = $quote->getAllVisibleItems();
        if (!$items || !$country || !$currency) {
            return null;
        }

        $query = [
            'country' => $country,
            'currency' => $currency,
            'flow_session_id' => $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE),
            'experience' => $this->getSessionExperienceKey()
        ];

        $orderForm = (object)[
            'attributes' => [
                WebhookEvent::QUOTE_ID => $quote->getId(),
                WebhookEvent::CHECKOUT_SESSION_ID => $this->checkoutSession->getSessionId(),
                WebhookEvent::CUSTOMER_SESSION_ID => $this->customerSession->getSessionId()
            ]
        ];

        $customer = $this->customerSession->getCustomer();
        if ($customer->getId()) {
            $orderForm->attributes[WebhookEvent::CUSTOMER_ID] = (string)$customer->getId();

            // Add customer info
            $orderForm->customer = (object)[
                'name' => (object)[
                    'first' => $customer->getFirstname(),
                    'last' => $customer->getLastname(),
                ],
                'number' => (string)$customer->getId(),
                'phone' => $customer->getTelephone(),
                'email' => $customer->getEmail()
            ];

            // Add default shipping address
            if ($shippingAddressId = $customer->getDefaultShipping()) {
                $shippingAddress = $this->addressRepository->getById($shippingAddressId);

                // Using strpos since country codes coming from FlowJS will not match M2 country codes directly
                if (strpos($country, $shippingAddress->getCountryId()) !== false) {
                    $orderForm->destination = (object)[
                        'streets' => $shippingAddress->getStreet(),
                        'city' => $shippingAddress->getCity(),
                        'province' => $shippingAddress->getRegion()->getRegionCode(),
                        'postal' => $shippingAddress->getPostcode(),
                        'country' => $country,
                        'contact' => (object)[
                            'name' => (object)[
                                'first' => $shippingAddress->getFirstname(),
                                'last' => $shippingAddress->getLastname(),
                            ],
                            'company' => $shippingAddress->getCompany(),
                            'email' => $customer->getEmail(),
                            'phone' => $shippingAddress->getTelephone()
                        ]
                    ];
                }
            }
        }

        // Add cart items
        $orderForm->items = [];
        foreach ($items as $item) {
            $lineItem = (object) [
                'number' => $item->getSku(),
                'quantity' => $item->getQty(),
            ];
            $itemRowTotal = $item->getRowTotal();
            $itemDiscountAmount = $item->getDiscountAmount();
            $itemDiscountPercentage = 0.0;
            if ($itemRowTotal > 0 && $itemDiscountAmount > 0) {
                $itemDiscountPercentage = (float)(($itemDiscountAmount / $itemRowTotal) * 100);
            }

            if ($itemDiscountPercentage > 0) {
                $lineItem->discounts = (object)[
                    'discounts' => [
                        (object)[
                            'offer' => (object)[
                                'discriminator' => 'discount_offer_percent',
                                'percent' => $itemDiscountPercentage
                            ],
                            'label' => 'Discount'
                        ]
                    ]
                ];
            }

            $info_buyRequest = $item->getProductOptionByCode(self::INFO_BUYREQUEST_LABEL); 
            if ($info_buyRequest) {
                $lineItem->attributes = [
                    self::INFO_BUYREQUEST_LABEL => $info_buyRequest
                ];
            }
            $orderForm->items[] = $lineItem;
        }

        $sessionId = $this->cookieManagerInterface->getCookie(self::FLOW_SESSION_COOKIE);

        if ($sessionId) {
            $createdOrder = json_decode($this->orderSave->execute($orderForm, $query, $sessionId));
            $tokenId = $this->orderSave->createCheckoutToken($createdOrder->number, $sessionId);
            if (isset($createdOrder->number) && $tokenId) {
                $result = $this->configuration->getFlowCheckoutBaseUrl() . '/tokens/' . $tokenId;

            }
        }

        return $result;
    }

    /**
     * Get session experience currency
     * @return string|null
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function getSessionExperienceCurrency()
    {
        $sessionData = $this->getFlowSessionData();
        $currency = isset($sessionData['local']['currency']['iso_4217_3']) ? $sessionData['local']['currency']['iso_4217_3'] : null;
        return $currency;
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
        $country = isset($sessionData['local']['country']['iso_3166_3']) ? $sessionData['local']['country']['iso_3166_3'] : null;
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
        if ($key = isset($sessionData['experience']['key']) ? $sessionData['experience']['key'] : null) {
            $this->logger->info('Current Experience Key: '.$key);
        }
        return $key;
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
