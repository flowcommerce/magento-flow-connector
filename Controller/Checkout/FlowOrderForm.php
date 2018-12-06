<?php

namespace FlowCommerce\FlowConnector\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use FlowCommerce\FlowConnector\Model\Util;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller class that returns a Flow order_form object for use with Flow.js.
 *
 * https://docs.flow.io/type/order-form
 */
class FlowOrderForm extends \Magento\Framework\App\Action\Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
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
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * FlowOrderForm constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     * @param Util $util
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param AddressRepositoryInterface $addressRepository
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Data $jsonHelper,
        \FlowCommerce\FlowConnector\Model\Util $util,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        CheckoutSession $checkoutSession,
        AddressRepositoryInterface $addressRepository,
        CookieManagerInterface $cookieManager
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->util = $util;
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
     * @throws LocalizedException
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
     * @throws LocalizedException
     */
    private function getFlowOrderForm()
    {
        $quote = $this->checkoutSession->getQuote();
        $data = [];

        // Additional custom attributes to pass through hosted checkout
        $attribs = [];
        $attribs[WebhookEvent::CHECKOUT_SESSION_ID] = $this->checkoutSession->getSessionId();
        $attribs[WebhookEvent::QUOTE_ID] = $quote->getId();

        if ($customer = $this->customerSession->getCustomer()) {
            $attribs[WebhookEvent::CUSTOMER_ID] = $this->customerSession->getId();
            $attribs[WebhookEvent::CUSTOMER_SESSION_ID] = $this->customerSession->getSessionId();
            $attribs[WebhookEvent::QUOTE_APPLIED_RULE_IDS] = $quote->getAppliedRuleIds();

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
        if ($items = $quote->getItems()) {
            $data['items'] = [];
            foreach($items as $item) {
                $lineItem = [
                    'number' => $item->getSku(),
                    'quantity' => $item->getQty(),
                    'discount' => [
                        'amount' => $item->getBaseDiscountAmount(),
                        'currency' => $quote->getBaseCurrencyCode()
                    ]
                ];
                array_push($data['items'], $lineItem);
            }
        }

        // Add custom attributes
        $data['attributes'] = $attribs;

        return $data;
    }
}
