<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\CheckoutSupportRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\Rule;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class CheckoutSupportRepository
 * @package FlowCommerce\FlowConnector\Model
 */
class CheckoutSupportRepository implements CheckoutSupportRepositoryInterface
{
    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var Coupon
     */
    protected $coupon;

    /**
     * @var Rule
     */
    protected $saleRule;     

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @param JsonSerializer $jsonSerializer
     * @param StoreManager $storeManager
     * @param QuoteFactory $quoteFactory
     * @param QuoteManagement $quoteManagement
     * @param ProductRepository $productRepository
     * @param ProductFactory $productFactory
     * @param CustomerFactory $customerFactory
     * @param CustomerRepository $customerRepository
     * @param Logger $logger
     */
    public function __construct(
        JsonSerializer $jsonSerializer,
        StoreManager $storeManager,
        QuoteFactory $quoteFactory,
        QuoteManagement $quoteManagement,
        CartRepositoryInterface $quoteRepository,
        Cart $cart,
        ProductRepository $productRepository,
        Product $product,
        ProductFactory $productFactory,
        FormKey $formKey,
        Coupon $coupon,
        Rule $saleRule,
        CustomerFactory $customerFactory,
        CustomerRepository $customerRepository,
        Logger $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->cart  = $cart;
        $this->productRepository = $productRepository;
        $this->product = $product;
        $this->productFactory = $productFactory;
        $this->formKey = $formKey;
        $this->coupon = $coupon;
        $this->saleRule = $saleRule;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }
     
    /**
     * @param $order
     * @param $code
     * @return mixed
     */
    public function discountRequest($order = false, $code = false)
    {
        $this->logger->info('Fired discountRequest');

        if (!$this->isValidRule($code)) {
            return;
        }

        $receivedOrder = $order;

        if (array_key_exists('attributes', $receivedOrder)) {
            if (array_key_exists('quote_id', $receivedOrder['attributes'])) {
                $store = $this->getStoreByQuoteId($receivedOrder['attributes']['quote_id']);
            }
        }
        
        ////////////////////////////////////////////////////////////
        // Create quote
        ////////////////////////////////////////////////////////////

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setCurrencyCode($receivedOrder['total']['currency']);
        $quote->setCurrency();

        $customer = $this->customerFactory->create();
        $customer->setStoreId($store->getId());
        $customer->setWebsiteId($store->getWebsiteId());
        
        // Check for existing customer
        if (array_key_exists('customer', $receivedOrder)) {
            $receivedCustomer = $receivedOrder['customer'];

            if (array_key_exists('email', $receivedCustomer)) {
                $customer = $customer->loadByEmail($receivedCustomer['email']);

                if ($customer->getEntityId()) {
                    $this->logger->info('Found customer by email: ' . $receivedCustomer['email']);
                } else {
                    if (array_key_exists('name', $receivedCustomer)) {
                        if (array_key_exists('first', $receivedCustomer['name']) &&
                            array_key_exists('last', $receivedCustomer['name'])) {
                            $customer->setFirstname($receivedCustomer['name']['first']);
                            $customer->setLastname($receivedCustomer['name']['last']);
                            $customer->setEmail($receivedCustomer['email']);
                            $customer = $customer->save();
                        }
                    }
                }
            }
        }

        // No customer found, create a new customer
        if (!$customer->getEntityId()) {
            $customer->setFirstname('John');
            $customer->setLastname('Doe');
            $customer->setEmail('example@email.com');
            $customer = $customer->save();
        }

        $customer = $this->customerRepository->getById($customer->getEntityId());
        $quote->assignCustomer($customer);

        ////////////////////////////////////////////////////////////
        // Add order items
        // https://docs.flow.io/type/localized-line-item
        ////////////////////////////////////////////////////////////

        foreach($receivedOrder['lines'] as $line) {
            $catalogProduct = $this->productRepository->get($line['item_number']);
            $product = $this->productFactory->create()->load($catalogProduct->getId());
            $product->setPrice($line['price']['amount']);
            $quote->addProduct($product, $line['quantity']);
        }
        $quote->setCouponCode($code); 
        $quote->collectTotals()->save();
        $items = $quote->getAllItems();

        $orderDiscountAmount = 0.0;
        $orderTotal = 0.0;
        foreach($items as $item) {
            $orderTotal += $item->getRowTotal();
            $orderDiscountAmount += $item->getDiscountAmount();
        }
        $orderCurrency = $receivedOrder['total']['currency'];
        $this->logger->info('Order Total: ' . $orderTotal);
        $this->logger->info('Discount Amount: ' . $orderDiscountAmount);

        if ($orderDiscountAmount <= 0) {
            return;
        }

        // TODO THIS ISNT THE RIGHT STRUCTURE, REFERENCE https://app.apibuilder.io/flow/experience-internal/0.6.27#model-discount_request_order_form
        $result = [
            "order_form" => [
                "order_entitlement_forms" => [
                    [
                        "entitlement_key" => [
                            "subtotal" => "subtotal"
                        ],
                        "offer_form" => [
                            "discriminator" => "discount_request_offer_fixed_amount_form",
                            "amount" => $orderDiscountAmount,
                            "currency" => $orderCurrency
                        ]
                    ]
                ]
            ]
        ];

        return json_encode($result);
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function isValidRule($code)
    {
        // Check if order is present in payload
        if (!$ruleId = $this->coupon->loadByCode($code)->getRuleId()) {
            $this->logger->info('Coupon code provided is not valid: ' . (string)$code);
            return false;
        }

        $rule = $this->saleRule->load($ruleId);
// Check if coupon code is a cart rule
        if ($rule->getData('coupon_type') != '2') {
            $this->logger->info('Non-cart rule coupon codes are not supported at this time: ' . (string)$code);
            return false;
        }

        if ($rule->getData('simple_action') == 'buy_x_get_y') {
            $this->logger->info('"Buy X Get Y" discounts are not supported at this time: ' . (string)$code);
            return false;
        }

        if ($rule->getData('apply_to_shipping') > 0) {
            $this->logger->info('Shipping discounts are not supported at this time: ' . (string)$code);
            return false;
        }

        $this->logger->info(json_encode($rule->getData()));
        $actionData = json_decode($rule->getData('actions_serialized'));
        if (isset($actionData->conditions)) {
            foreach ($actionData->conditions as $actions) {
                $attribute = $actions->attribute; 
                if ($attribute != null || isset($actions->conditions)) {
                    $this->logger->info('Coupon code provided contains actions not supported by Flow: ' . (string)$code);
                    return false;
                }
            } 
        }

        $this->logger->info('Found valid code: ' . $code);
        return $rule;
    }

    /**
     * @param $quoteId
     * @return mixed
     */
    protected function getStoreByQuoteId($quoteId)
    {
        $store = false;
        $quote = $this->quoteRepository->get($quoteId);

        if ($storeId = $quote->getStoreId()) {
            $store = $this->storeManager->getStore($storeId);
        }

        if (!$store) {
            $store = $this->storeManager->getStore();
        }

        $this->logger->info('Store: ' . $storeId);

        return $store;
    }
}
