<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\CheckoutSupportRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\Rule;
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
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var Coupon
     */
    protected $coupon;

    /**
     * @var Rule
     */
    protected $saleRule;     

    /**
     * @param JsonSerializer $jsonSerializer
     * @param StoreManager $storeManager
     * @param QuoteFactory $quoteFactory
     * @param QuoteManagement $quoteManagement
     * @param ProductRepository $productRepository
     * @param ProductFactory $productFactory
     * @param Logger $logger
     */
    public function __construct(
        JsonSerializer $jsonSerializer,
        StoreManager $storeManager,
        QuoteFactory $quoteFactory,
        QuoteManagement $quoteManagement,
        CartRepositoryInterface $quoteRepository,
        ProductRepository $productRepository,
        ProductFactory $productFactory,
        Coupon $coupon,
        Rule $saleRule,
        Logger $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->coupon = $coupon;
        $this->saleRule = $saleRule;
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
        
        ////////////////////////////////////////////////////////////
        // Create quote
        ////////////////////////////////////////////////////////////

        $quote = $this->quoteFactory->create();
        $quote->setStoreId($store->getId());
        $quote->setCurrencyCode($receivedOrder['total']['currency']);
        $quote->setBaseCurrencyCode($receivedOrder['total']['base']['currency']);

        ////////////////////////////////////////////////////////////
        // Add order items
        // https://docs.flow.io/type/localized-line-item
        ////////////////////////////////////////////////////////////

        foreach($receivedOrder['lines'] as $line) {
            $this->logger->info('Looking up product: ' . $line['item_number']);
            $catalogProduct = $this->productRepository->get($line['item_number']);
            $product = $this->productFactory->create()->load($catalogProduct->getId());
            $product->setPrice($line['price']['amount']);
            $product->setBasePrice($line['price']['base']['amount']);

            $this->logger->info('Adding product to quote: ' . $product->getSku());
            $this->logger->info(json_encode($product->getData()));
            $params = [
                "product" => (string)$product->getId(),
                "qty" => (string)$line['quantity']
            ];
            $request = new \Magento\Framework\DataObject();
            $request->setData($params);
            // TODO this add product to quote method is not working as intended
            $quote->addProduct($product, $request);
            $this->logger->info(json_encode($quote->getData()));
            $this->logger->info(json_encode($quote->getItemsCollection()));
        }
        $quote->setCouponCode($code);

        /* $rule = $this->saleRule->load($ruleId); */
        /* $orderDiscountAmount = $rule->getDiscountAmount(); */
        /* $orderDiscountAmount = $quote->getBaseDiscountAmount(); */

        $orderDiscountAmount = 0.0;
        $orderTotal = 0.0;
        foreach($quote->getAllVisibleItems() as $item) {
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
