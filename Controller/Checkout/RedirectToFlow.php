<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use FlowCommerce\FlowConnector\Helper\Data;
use FlowCommerce\FlowConnector\Model\Api\UrlBuilder;
use FlowCommerce\FlowConnector\Model\Config\Source\DataSource;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use FlowCommerce\FlowConnector\Model\Util;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller class for redirecting to Flow's hosted checkout.
 */
class RedirectToFlow extends \Magento\Framework\App\Action\Action
{
    const URL_STUB_PREFIX = '/order/';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var Data
     */
    private $flowHelper;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * RedirectToFlow constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param JsonHelper $jsonHelper
     * @param Data $flowHelper
     * @param Util $util
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param Session $checkoutSession
     * @param AddressRepositoryInterface $addressRepository
     * @param CookieManagerInterface $cookieManager
     * @param UrlBuilder $urlBuilder
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        JsonHelper $jsonHelper,
        Data $flowHelper,
        \FlowCommerce\FlowConnector\Model\Util $util,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        Session $checkoutSession,
        AddressRepositoryInterface $addressRepository,
        CookieManagerInterface $cookieManager,
        UrlBuilder $urlBuilder
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->flowHelper = $flowHelper;
        $this->util = $util;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->addressRepository = $addressRepository;
        $this->cookieManager = $cookieManager;
        $this->urlBuilder = $urlBuilder;

        parent::__construct($context);
    }

    /**
     * Redirects to Flow checkout
     *
     * The Flow experience can be set by passing in the "country" URL param.
     *
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $url = null;

        $quote = $this->checkoutSession->getQuote();
        if ($quote->hasItems()) {
            $url = $this->getCheckoutUrlWithCart($quote, $this->getRequest()->getParam("country"));
        } else {
            $url = $this->storeManager->getStore()->getBaseUrl();
        }

        $this->logger->info('URL: ' . $url);

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $result->setUrl($url);
        return $result;
    }

    /**
     * Returns the full Flow checkout url with the specified items.
     * https://docs.flow.io/checkout/checkout
     *
     * @param Quote $quote
     * @param null $country
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCheckoutUrlWithCart($quote, $country = null)
    {
        /** @var Item[] $items */
        $items = $quote->getItems();

        $url = $this->urlBuilder->getFlowCheckoutEndpoint(
            self::URL_STUB_PREFIX,
            $this->storeManager->getStore()->getId()
        ) . '?';

        // Additional custom attributes to pass through hosted checkout
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $quote->getId();

        $params = [];

        if ($country) {
            $params['country'] = $country;
        }

        if ($flowSessionId = $this->cookieManager->getCookie(Util::FLOW_SESSION_COOKIE)) {
            $params['flow_session_id'] = $flowSessionId;
        }

        if ($customer = $this->customerSession->getCustomer()) {
            $attribs[WebhookEvent::CUSTOMER_ID] = $customer->getId();
            $attribs[WebhookEvent::CUSTOMER_SESSION_ID] = $this->customerSession->getSessionId();
            $attribs[WebhookEvent::QUOTE_APPLIED_RULE_IDS] = $quote->getAppliedRuleIds();

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
        $currencyCode = $quote->getBaseCurrencyCode();
        foreach ($items as $item) {
            $params['items[' . $ctr . '][number]'] = $item->getSku();
            $params['items[' . $ctr . '][quantity]'] = $item->getQty();
            $params['items[' . $ctr . '][discount]'] = [
                'amount' => $item->getBaseDiscountAmount(),
                'currency' => $currencyCode
            ];

            $ctr += 1;
        }

        // Add custom attributes
        foreach ($attribs as $k => $v) {
            $params['attributes[' . $k . ']'] = $v;
        }

        $this->logger->info('CART: ' . $this->jsonHelper->jsonEncode($params));

        $url = $url . http_build_query($params);
        return $url;
    }
}
