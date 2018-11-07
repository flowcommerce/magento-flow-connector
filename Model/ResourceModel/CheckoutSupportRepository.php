<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\CheckoutSupportRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
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
     * @var ProductRepository
     */
    protected $productRepository;

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
        CartRepositoryInterface $quoteRepository,
        ProductRepository $productRepository,
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
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
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
        $this->logger->info('Coupon code provided: ' . (string)$code);

        if (!$this->isValidRule($code)) {
            return;
        }

        if (!array_key_exists('total', $order)) {
            $this->logger->info('Missing order totals');
            return;
        }

        if (!array_key_exists('currency', $order['total'])) {
            $this->logger->info('Missing order currency');
            return;
        }

        $orderCurrency = $order['total']['currency'];
        $this->logger->info('Currency used: ' . $orderCurrency);

        $quote = $this->generateQuote($order);
        $quote->setCouponCode($code); 
        $quote->collectTotals()->save();

        $items = $quote->getAllItems();
        $orderDiscountAmount = 0.0;
        $orderTotal = 0.0;
        foreach($items as $item) {
            $orderTotal += $item->getRowTotal();
            $orderDiscountAmount += $item->getDiscountAmount();
        }

        $quote->delete();

        if ($orderDiscountAmount <= 0) {
            $this->logger->info('No discount applicable');
            return;
        }
        $this->logger->info('Discount amount found: ' . $orderDiscountAmount);

        // TODO THIS ISNT THE RIGHT STRUCTURE, REFERENCE https://app.apibuilder.io/flow/experience-internal/0.6.27#model-discount_request_order_form
        $result = [
            "amount" => $orderDiscountAmount,
            "currency" => $orderCurrency
        ];

        return json_encode($result);
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function isValidRule($code)
    {
        if (!$ruleId = $this->coupon->loadByCode($code)->getRuleId()) {
            $this->logger->info('Code does not exist');
            return false;
        }

        $rule = $this->saleRule->load($ruleId);

        if ($rule->getData('coupon_type') != '2') {
            $this->logger->info('Non-cart rule coupon codes are not supported at this time');
            return false;
        }

        if ($rule->getData('simple_action') == 'buy_x_get_y') {
            $this->logger->info('"Buy X Get Y" discounts are not supported at this time');
            return false;
        }

        if ($rule->getData('apply_to_shipping') > 0) {
            $this->logger->info('Shipping discounts are not supported at this time');
            return false;
        }

        $actionData = json_decode($rule->getData('actions_serialized'));
        if (isset($actionData->conditions)) {
            foreach ($actionData->conditions as $actions) {
                $attribute = $actions->attribute; 
                if ($attribute != null || isset($actions->conditions)) {
                    $this->logger->info('Action conditions are not supported at this time');
                    return false;
                }
            } 
        }

        $this->logger->info('Code validated');
        return $rule;
    }

    /**
     * @param $order
     * @return mixed
     */
    protected function generateQuote($order = false)
    {
        if (array_key_exists('attributes', $order)) {
            if (array_key_exists('quote_id', $order['attributes'])) {
                $store = $this->getStoreByQuoteId($order['attributes']['quote_id']);
            }
        }

        if (!$order || !$store) {
            return false;
        }

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setCurrencyCode($order['total']['currency']);
        $quote->setCurrency();

        $customer = $this->customerFactory->create();
        $customer->setStoreId($store->getId());
        $customer->setWebsiteId($store->getWebsiteId());
        
        if (array_key_exists('customer', $order)) {
            $receivedCustomer = $order['customer'];

            if (array_key_exists('email', $receivedCustomer)) {
                $customer = $customer->loadByEmail($receivedCustomer['email']);

                if (!$customer->getEntityId() &&
                    array_key_exists('name', $receivedCustomer)) {
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

        if (!$customer->getEntityId() &&
            !$customer->loadByEmail('example@email.com')) {
            $customer->setFirstname('John');
            $customer->setLastname('Doe');
            $customer->setEmail('example@email.com');
            $customer = $customer->save();
        }
        $customer = $this->customerRepository->getById($customer->getEntityId());
        $quote->assignCustomer($customer);

        if (!array_key_exists('lines', $order)) {
            $this->logger->info('Missing order lines');
            return false;
        }

        foreach($order['lines'] as $line) {
            $catalogProduct = $this->productRepository->get($line['item_number']);
            $product = $this->productFactory->create()->load($catalogProduct->getId());
            $product->setPrice($line['price']['amount']);
            $quote->addProduct($product, $line['quantity']);
        }

        return $quote;
    }

    /**
     * @param $quoteId
     * @return mixed
     */
    protected function getStoreByQuoteId($quoteId = false)
    {
        $store = false;
        $quote = $this->quoteRepository->get($quoteId);

        if ($storeId = $quote->getStoreId()) {
            $store = $this->storeManager->getStore($storeId);
        }

        if (!$store) {
            $store = $this->storeManager->getStore();
        }

        $this->logger->info('Store ID: ' . $storeId);

        return $store;
    }
}
