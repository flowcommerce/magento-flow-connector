<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;
use FlowCommerce\FlowConnector\Model\Util;
use FlowCommerce\FlowConnector\Model\WebhookEvent;

/**
 * Controller class for redirecting to Flow's hosted checkout.
 */
class RedirectToFlow extends \Magento\Framework\App\Action\Action {

    protected $logger;
    protected $jsonHelper;
    protected $util;
    protected $cart;
    protected $storeManager;
    protected $customerSession;
    protected $checkoutSession;
    protected $addressRepository;
    protected $cookieManager;

    /**
    * @param \Magento\Framework\App\Action\Context $context
    * @param \Psr\Log\LoggerInterface $logger
    */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \FlowCommerce\FlowConnector\Model\Util $util,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->util = $util;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->addressRepository = $addressRepository;
        $this->cookieManager = $cookieManager;
        parent::__construct($context);
    }

    /**
    * Redirects to Flow checkout
    *
    * The Flow experience can be set by passing in the "country" URL param.
    *
    * @return void
    */
    public function execute()
    {
        $url = null;

        if ($items = $this->cart->getQuote()->getItems()) {
            $url = $this->getCheckoutUrlWithCart($items, $this->getRequest()->getParam("country"));
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
    * @param items List of items for checkout
    * @param country Country code for the experience
    */
    private function getCheckoutUrlWithCart($items, $country = null) {
        $url = $this->util->getFlowCheckoutUrl() . '?';

        // Additional custom attributes to pass through hosted checkout
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $this->checkoutSession->getQuote()->getId();

        $params = [];

        if ($country) {
            $params['country'] = $country;
        }

        if ($flowSessionId = $this->cookieManager->getCookie(Util::FLOW_SESSION_COOKIE)) {
            $params['flow_session_id'] = $flowSessionId;
        }

        if ($customer = $this->customerSession->getCustomer()) {
            $attribs[WebhookEvent::CUSTOMER_ID] = $this->customerSession->getId();
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
                foreach($shippingAddress->getStreet() as $street) {
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
        foreach($items as $item) {
            $params['items[' . $ctr . '][number]'] = $item->getSku();
            $params['items[' . $ctr . '][quantity]'] = $item->getQty();
            $ctr += 1;
        }

        // Add custom attributes
        foreach($attribs as $k => $v) {
            $params['attributes[' . $k . ']'] = $v;
        }

        $this->logger->info('CART: ' . $this->jsonHelper->jsonEncode($params));

        $url = $url . http_build_query($params);
        return $url;
    }
}
