<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use Magento\Framework\Controller\ResultFactory;
use FlowCommerce\FlowConnector\Model\Util;
use FlowCommerce\FlowConnector\Model\WebhookEvent;

/**
 * Controller class that returns a Flow order_form object for use with Flow.js.
 *
 * https://docs.flow.io/type/order-form
 */
class FlowOrderForm extends \Magento\Framework\App\Action\Action {

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
    * Returns a JSON response for a Flow order_form object.
    *
    * @return string https://docs.flow.io/type/order-form
    */
    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($this->getFlowOrderForm());
        return $response;
    }

    /**
    * Returns a Flow order_form object for use with Flow.js.
    *
    * @return string https://docs.flow.io/type/order-form
    */
    private function getFlowOrderForm() {

        $data = [];

        // Additional custom attributes to pass through hosted checkout
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $this->checkoutSession->getQuote()->getId();

        if ($customer = $this->customerSession->getCustomer()) {
            $attribs[WebhookEvent::CUSTOMER_ID] = $this->customerSession->getId();
            $attribs[WebhookEvent::CUSTOMER_SESSION_ID] = $this->customerSession->getSessionId();

            // Add customer info
            $data['customer'] = [
                'name' => [
                    'first' => $customer->getFirstname(),
                    'last' => $customer->getLastname()
                ],
                'number' => $customer->getId(),
                'phone' => $customer->getTelephone(),
                'email' => $customer->getEmail()
            ];

            // Add default shipping address
            if ($shippingAddressId = $customer->getDefaultShipping()) {
                $shippingAddress = $this->addressRepository->getById($shippingAddressId);

                $data['destination'] = [
                     'streets' => $shippingAddress->getStreet(),
                     'city' => $shippingAddress->getCity(),
                     'province' => $shippingAddress->getRegion()->getRegionCode(),
                     'postal' => $shippingAddress->getPostcode(),
                     'country' => $shippingAddress->getCountryId(),
                     'contact' => [
                         'name' => [
                             'first' => $shippingAddress->getFirstname(),
                             'last' => $shippingAddress->getLastname()
                         ],
                         'company' => $shippingAddress->getCompany(),
                         'email' => $customer->getEmail(),
                         'phone' => $shippingAddress->getTelephone()
                     ]
                ];

            }
        }

        // Add cart items
        if ($items = $this->cart->getQuote()->getItems()) {
            $data['items'] = [];
            foreach($items as $item) {
                $lineItem = [
                    'number' => $item->getSku(),
                    'quantity' => $item->getQty()
                ];
                array_push($data['items'], $lineItem);
            }
        }

        // Add custom attributes
        $data['attributes'] = $attribs;

        return $data;
    }
}
