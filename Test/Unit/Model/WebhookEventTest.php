<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

/**
 * Test class for WebhookEvent.
 */
class WebhookEventTest extends \PHPUnit\Framework\TestCase
{
    protected $logger;
    protected $jsonHelper;
    protected $storeManager;
    protected $productFactory;
    protected $productRepository;
    protected $quoteFactory;
    protected $quoteManagement;
    protected $customerFactory;
    protected $customerRepository;
    protected $orderService;
    protected $cartRepository;
    protected $cartManagement;
    protected $shippingRate;
    protected $currency;
    protected $countryFactory;
    protected $regionFactory;
    protected $orderFactory;
    protected $methodList;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $quotePaymentFactory;
    protected $eventManager;

    protected function setUp(): void
    {
        $this->context = $this->createMock(\Magento\Framework\Model\Context::class);
        $this->registry = $this->createMock(\Magento\Framework\Registry::class);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->productFactory = $this->createMock(\Magento\Catalog\Model\ProductFactory::class);
        $this->productRepository = $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->quoteFactory = $this->createMock(\Magento\Quote\Model\QuoteFactory::class);
        $this->quoteManagement = $this->createMock(\Magento\Quote\Model\QuoteManagement::class);
        $this->customerFactory = $this->createMock(\Magento\Customer\Model\CustomerFactory::class);
        $this->customerRepository = $this->createMock(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $this->orderService = $this->createMock(\Magento\Sales\Model\Service\OrderService::class);
        $this->cartRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->cartManagement = $this->createMock(\Magento\Quote\Api\CartManagementInterface::class);
        $this->shippingRate = $this->createMock(\Magento\Quote\Model\Quote\Address\Rate::class);
        $this->currency = $this->createMock(\Magento\Quote\Api\Data\CurrencyInterface::class);
        $this->countryFactory = $this->createMock(\Magento\Directory\Model\CountryFactory::class);
        $this->regionFactory = $this->createMock(\Magento\Directory\Model\RegionFactory::class);
        $this->orderFactory = $this->createMock(\Magento\Sales\Model\OrderFactory::class);
        $this->methodList = $this->createMock(\Magento\Payment\Model\MethodList::class);
        $this->orderRepository = $this->createMock(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->quotePaymentFactory = $this->createMock(\Magento\Quote\Model\Quote\PaymentFactory::class);
        $this->eventManager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
    }

    /**
     * Test WebhookEvent.process
     */
    public function testProcess()
    {
        // $webhookEvent = new \FlowCommerce\FlowConnector\Model\WebhookEvent(
        //     $this->context,
        //     $this->registry,
        //     $this->logger,
        //     $this->jsonHelper,
        //     $this->storeManager,
        //     $this->productFactory,
        //     $this->productRepository,
        //     $this->quoteFactory,
        //     $this->quoteManagement,
        //     $this->customerFactory,
        //     $this->customerRepository,
        //     $this->orderService,
        //     $this->cartRepository,
        //     $this->cartManagement,
        //     $this->shippingRate,
        //     $this->currency,
        //     $this->countryFactory,
        //     $this->regionFactory,
        //     $this->orderFactory,
        //     $this->methodList,
        //     $this->orderRepository,
        //     $this->searchCriteriaBuilder,
        //     $this->quotePaymentFactory,
        //     $this->eventManager,
        //     null,
        //     null,
        //     []
        // );
        // $webhookEvent->process();
    }
}
