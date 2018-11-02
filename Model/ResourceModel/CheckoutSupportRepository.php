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
        $this->logger->info('-----Fired discountRequest-----');

        // Check if order is present in payload
        if (!$ruleId = $this->coupon->loadByCode($code)->getRuleId()) {
            $this->logger->info('Coupon code provided is not valid: ' . (string)$code);
            return;
        }

        $receivedOrder = $order;
        $store = false;
        $this->logger->info('Found valid code: ' . $code);

        if (isset($receivedOrder['attributes']['quote_id'])) {
            $oldQuote = $this->quoteRepository->get($receivedOrder['attributes']['quote_id']);
            if ($storeId = $oldQuote->getStoreId()) {
                $store = $this->storeManager->getStore($storeId);
            }
        } 
        if (!$store) {
            $store = $this->storeManager->getStore();
        }
        $this->logger->info('Store: ' . $storeId);
        
        /* $this->cart->setStoreId($store->getId()); */
        /* $this->cart->setCurrencyCode($receivedOrder['total']['currency']); */
        /* $this->cart->setBaseCurrencyCode($receivedOrder['total']['base']['currency']); */

        /* //////////////////////////////////////////////////////////// */
        /* // Add order items */
        /* // https://docs.flow.io/type/localized-line-item */
        /* //////////////////////////////////////////////////////////// */

        /* foreach($receivedOrder['lines'] as $line) { */
        /*     $this->logger->info('Looking up product: ' . $line['item_number']); */
        /*     $catalogProduct = $this->productRepository->get($line['item_number']); */
        /*     $product = $this->product->load($catalogProduct->getId()); */
        /*     $product->setPrice($line['price']['amount']); */
        /*     $product->setBasePrice($line['price']['base']['amount']); */

        /*     $this->logger->info('Adding product to cart: ' . $product->getSku()); */
        /*     $this->logger->info(json_encode($product->getOptions())); */
        /*     $this->logger->info(json_encode($product->getData())); */
        /*     $params = [ */
        /*         "form_key" => $this->formKey->getFormKey(), */
        /*         "product" => $product->getId(), */
        /*         "qty" => $line['quantity'], */
        /*     ]; */
        /*     $this->logger->info(json_encode($params)); */
        /*     /1* $request = new \Magento\Framework\DataObject(); *1/ */
        /*     /1* $request->setData($params); *1/ */
        /*     // TODO this add product to cart method is not working as intended */
        /*     $this->cart->addProduct($product, $params); */
        /*     $this->cart->save(); */
        /*     $this->logger->info(json_encode($this->cart->getData())); */
        /*     $this->logger->info(json_encode($this->cart->getQuote()->getAllItems())); */
        /* } */

        ////////////////////////////////////////////////////////////
        // Create quote
        ////////////////////////////////////////////////////////////

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setBaseCurrencyCode($receivedOrder['total']['base']['currency']);
        $quote->setCurrencyCode($receivedOrder['total']['currency']);
        $quote->setCurrency();

        $customer = $this->customerFactory->create();
        $customer->setStoreId($store->getId());
        $customer->setWebsiteId($store->getWebsiteId());
        if ($customer = $customer->loadByEmail('test@flow.io')) {
            $customer->setFirstname('Test');
            $customer->setLastname('Test');
            $customer->setEmail('test@flow.io');
            $customer->save();
        }
        $customer = $this->customerRepository->getById($customer->getEntityId());
        $quote->assignCustomer($customer);

        ////////////////////////////////////////////////////////////
        // Add order items
        // https://docs.flow.io/type/localized-line-item
        ////////////////////////////////////////////////////////////

        foreach($receivedOrder['lines'] as $line) {
            $this->logger->info('Looking up product: ' . $line['item_number']);
            $catalogProduct = $this->productRepository->get($line['item_number']);
            $product = $this->productFactory->create()->load($catalogProduct->getId());
            $product->setBasePrice(intval($line['price']['base']['amount']));
            $product->setPrice(intval($line['price']['amount']));
            $this->logger->info(json_encode($product->getData())); 
            $this->logger->info('Adding product to quote: ' . $product->getSku());
            $quote->addProduct($product, intval($line['quantity']));
        }
        $quote->setCouponCode($code); 
        $quote->collectTotals()->save();
        $items = $quote->getAllItems();
        $this->logger->info(json_encode($quote->getData()));
        $this->logger->info(json_encode($items));

        /* $rule = $this->saleRule->load($ruleId); */
        /* $orderDiscountAmount = $rule->getDiscountAmount(); */
        /* $orderDiscountAmount = $this->cart->getBaseDiscountAmount(); */

        $orderDiscountAmount = 0.0;
        $orderTotal = 0.0;
        foreach($items as $item) {
            $orderTotal += $item->getRowTotal();
            $orderDiscountAmount += $item->getDiscountAmount();
        }
        $orderCurrency = $receivedOrder['total']['currency'];
        $orderBaseCurrency = $receivedOrder['total']['base']['currency'];
        $this->logger->info('Discount Amount: ' . $orderDiscountAmount);
        $this->logger->info('Order Total: ' . $orderTotal);

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
                            "currency" => $orderBaseCurrency
                        ]
                    ]
                ]
            ]
        ];

        return json_encode($result);
    }
}
