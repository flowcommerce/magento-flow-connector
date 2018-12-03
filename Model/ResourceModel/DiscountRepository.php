<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\Data\DiscountInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote\AddressFactory as QuoteAddressFactory;
use Magento\Quote\Api\CartRepositoryInterface as CartRepository;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Psr\Log\LoggerInterface as Logger;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\Rule;
use \FlowCommerce\FlowConnector\Model\Discount;
use \FlowCommerce\FlowConnector\Api\Data\DiscountRepositoryInterface;
use \FlowCommerce\FlowConnector\Exception\DiscountException;

/**
 * Class DiscountRepository
 * @package FlowCommerce\FlowConnector\Model\ResourceModel
 */
class DiscountRepository implements DiscountRepositoryInterface
{
    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteAddressFactory
     */
    protected $quoteAddressFactory;

    /**
     * @var CartRepository
     */
    protected $cartRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Coupon
     */
    protected $coupon;

    /**
     * @var Rule
     */
    protected $saleRule;

    /**
     * @var Discount
     */
    protected $discount;

    /**
     * @param StoreManager $storeManager
     * @param QuoteFactory $quoteFactory
     * @param QuoteRepository $cartRepository
     * @param QuoteAddressFactory $quoteAddressFactory
     * @param ProductRepository $productRepository
     * @param ProductFactory $productFactory
     * @param CustomerFactory $customerFactory
     * @param CustomerRepository $customerRepository
     * @param Logger $logger
     * @param Coupon $coupon
     * @param SalesRule $salesRule
     * @param Discount $discount
     */
    public function __construct(
        StoreManager $storeManager,
        QuoteFactory $quoteFactory,
        QuoteAddressFactory $quoteAddressFactory,
        CartRepository $cartRepository,
        ProductRepository $productRepository,
        ProductFactory $productFactory,
        CustomerFactory $customerFactory,
        CustomerRepository $customerRepository,
        Logger $logger,
        Coupon $coupon,
        Rule $saleRule,
        Discount $discount
    ) {
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->coupon = $coupon;
        $this->saleRule = $saleRule;
        $this->discount = $discount;
    }
     
    /**
     * @param $order
     * @param $code
     * @return \FlowCommerce\FlowConnector\Api\Data\DiscountInterface
     * @throws \FlowCommerce\FlowConnector\Exception\DiscountException
     */
    public function getDiscount($order = false, $code = false)
    {
        $this->logger->info('Fired discountRequest');
        $this->logger->info('Coupon code provided: ' . (string)$code);

        if (!$this->isValidRule($code)) {
            throw new \FlowCommerce\FlowConnector\Exception\DiscountException();
        }

        if (!array_key_exists('total', $order)) {
            $this->logger->info('Missing order totals');
            throw new \FlowCommerce\FlowConnector\Exception\DiscountException();
        }

        if (!array_key_exists('currency', $order['total'])) {
            $this->logger->info('Missing order currency');
            throw new \FlowCommerce\FlowConnector\Exception\DiscountException();
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
            throw new \FlowCommerce\FlowConnector\Exception\DiscountException();
        }
        $this->logger->info('Discount amount found: ' . $orderDiscountAmount);

        $this->discount->addSubtotalDiscount($orderDiscountAmount, $orderCurrency);

        $this->logger->info(json_encode($this->discount));
        return $this->discount;
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
            }
        }

        if ($customer->getEntityId()) {
            $customer = $this->customerRepository->getById($customer->getEntityId());
            $quote->assignCustomer($customer);
        } else {
            $address = $this->quoteAddressFactory->create();
            $quote->setBillingAddress($address);
            $quote->setShippingAddress($address);
        }

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
        $quote = $this->cartRepository->get($quoteId);

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
