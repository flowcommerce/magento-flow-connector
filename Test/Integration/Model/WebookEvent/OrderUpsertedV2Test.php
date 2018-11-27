<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory as WebhookEventCollectionFactory;
use FlowCommerce\FlowConnector\Model\WebhookEvent as Subject;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateWebhookEvents;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\Order\ItemRepository as OrderItemRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Test class for \FlowCommerce\FlowConnector\Model\WebhookEvent
 * @magentoAppIsolation enabled
 */
class OrderUpsertedV2Test extends \PHPUnit\Framework\TestCase
{

    /**
     * @var CreateProductsWithCategories
     */
    private $createProductsFixture;

    /**
     * @var CreateWebhookEvents
     */
    private $createWebhookEventsFixture;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Subject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * @var WebhookEventCollectionFactory
     */
    private $webhookEventCollectionFactory;

    /**
     * @var OrderFactory
     */
    private $mageOrderFactory;

    /**
     * @var OrderRepository
     */
    private $mageOrderRepository;

    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var string[]
     */
    private $vatSubtotalPriceComponentKeys =
        ['vat_item_price', 'vat_deminimis', 'vat_duties_item_price', 'vat_subsidy'];

    /**
     * @var string[]
     */
    private $vatShippingComponentKeys = ['vat_deminimis', 'vat_freight', 'vat_duties_freight', 'vat_subsidy'];

    /**
     * @var string[]
     */
    private $dutySubtotalPriceComponentKeys = ['duties_item_price', 'duty_deminimis'];

    /**
     * @var string[]
     */
    private $dutyShippingPriceComponentKeys = ['duties_freight', 'duty_deminimis'];


    /**
     * Sets up for tests
     * @magentoDbIsolation enabled
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createWebhookEventsFixture = $this->objectManager->create(CreateWebhookEvents::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->countryFactory = $this->objectManager->create(CountryFactory::class);
        $this->mageOrderFactory = $this->objectManager->create(OrderFactory::class);
        $this->mageOrderRepository = $this->objectManager->create(OrderRepository::class);
        $this->orderItemRepository = $this->objectManager->create(OrderItemRepository::class);
        $this->webhookEventManager = $this->objectManager->create(WebhookEventManager::class);
        $this->webhookEventCollectionFactory = $this->objectManager->create(WebhookEventCollectionFactory::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $this->subject = $this->objectManager->create(Subject::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @throws \Exception
     * @throws LocalizedException
     */
    public function testOrderUpsertedV2()
    {
        $this->createProductsFixture->execute();
        $orderUpsertedEvents = $this->createWebhookEventsFixture->createOrderUpsertedWebhooks();

        //Process
        $this->webhookEventManager->process(1000, 1);

        //Test values in magento orders
        foreach ($orderUpsertedEvents as $orderUpsertedEvent) {
            $payload = $orderUpsertedEvent->getPayloadData();

            $flowOrderId = $payload['order']['number'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            $payloadOrderInfo = $payload['order'];

            $submittedAt = isset($payloadOrderInfo['submitted_at']) ? $payloadOrderInfo['submitted_at'] : null;

            $this->assertNotNull($submittedAt);


            /* TODO: Check behaviour on previously existing customer
            This test is commented because if the customer already exists, he is loaded by email and assigned to the
            order and the first and last names will be from the customer and not from the payload.
            FlowCommerce\FlowConnector\Model\WebhookEvent::processOrderUpsertedV2 L1119
            Customer Information
            $payloadCustomerInfo = $payloadOrderInfo['customer'];
            $this->assertEquals($payloadCustomerInfo['name']['first'], $order->getCustomerFirstname());
            $this->assertEquals($payloadCustomerInfo['name']['last'], $order->getCustomerLastname());
            $this->assertEquals($payloadCustomerInfo['email'], $order->getCustomerEmail());
            */

            //Extract prices info
            $pricesInfo = [];
            foreach ($payloadOrderInfo['prices'] as $price) {
                $pricesInfo[$price['key']] = $price;
            }

            //Order totals
            $this->assertEquals($payloadOrderInfo['total']['amount'], $order->getGrandTotal());
            $this->assertEquals($payloadOrderInfo['total']['base']['amount'], $order->getBaseGrandTotal());

            //Order Subtotal
            $this->assertEquals($pricesInfo['subtotal']['amount'], $order->getSubtotal());
            $this->assertEquals($pricesInfo['subtotal']['base']['amount'], $order->getBaseSubtotal());

            //Order Tax (Vat+Duty) amount
            $vatAmount = isset($pricesInfo['vat']['amount']) ? $pricesInfo['vat']['amount'] : 0;
            $dutyAmount = isset($pricesInfo['duty']['amount']) ? $pricesInfo['duty']['amount'] : 0;
            $taxAmount = $vatAmount + $dutyAmount;
            $baseVatAmount = isset($pricesInfo['vat']['base']['amount']) ? $pricesInfo['vat']['base']['amount'] : 0;
            $baseDutyAmount = isset($pricesInfo['duty']['base']['amount']) ? $pricesInfo['duty']['base']['amount'] : 0;
            $baseTaxAmount = $baseVatAmount + $baseDutyAmount;

            $this->assertEquals($baseTaxAmount, $order->getBaseTaxAmount());
            $this->assertEquals($taxAmount, $order->getTaxAmount());

            //Order discount
            $baseDiscount = isset($pricesInfo['discount']['base']['amount']) ?
                $pricesInfo['discount']['base']['amount'] : 0;
            $discount = isset($pricesInfo['discount']['amount']) ? $pricesInfo['discount']['amount'] : 0;
            $this->assertEquals($baseDiscount, $order->getBaseDiscountAmount());
            $this->assertEquals($discount, $order->getDiscountAmount());

            //Order Shipping
            $this->assertEquals($pricesInfo['shipping']['amount'], $order->getShippingAmount());
            $this->assertEquals($pricesInfo['shipping']['base']['amount'], $order->getBaseShippingAmount());

            //Order Currency code
            $this->assertEquals($payloadOrderInfo['total']['currency'], $order->getOrderCurrencyCode());
            $this->assertEquals($payloadOrderInfo['total']['base']['currency'], $order->getBaseCurrencyCode());


            //Subtotal Components
            $dutyAmount = 0.0;
            $baseDutyAmount = 0.0;
            $vatAmount =0.0;
            $baseVatAmount = 0.0;

            //Extract order subtotal components
            $subtotalComponents = $pricesInfo['subtotal']['components'];
            $shippingComponents = $pricesInfo['shipping']['components'];

            //Subtotal Components
            $subtotalCompInfo = [];
            foreach ($subtotalComponents as $component) {
                $subtotalCompInfo[$component['key']] = $component;
                if (in_array($component['key'], $this->vatSubtotalPriceComponentKeys)) {
                    $vatAmount += $component['amount'];
                    $baseVatAmount += $component['base']['amount'];
                } elseif (in_array($component['key'], $this->dutySubtotalPriceComponentKeys)) {
                    $dutyAmount  += $component['amount'];
                    $baseDutyAmount += $component['base']['amount'];
                }
            }

            //Shipping componets
            $shippingCompInfo = [];
            foreach ($shippingComponents as $component) {
                $shippingCompInfo[$component['key']] = $component;
                if (in_array($component['key'], $this->vatShippingComponentKeys)) {
                    $vatAmount += $component['amount'];
                    $baseVatAmount += $component['base']['amount'];
                } elseif (in_array($component['key'], $this->dutyShippingPriceComponentKeys)) {
                    $dutyAmount  += $component['amount'];
                    $baseDutyAmount += $component['base']['amount'];
                }
            }

            //Flow attributes
            $this->assertEquals($baseVatAmount, $order->getFlowConnectorBaseVat());
            $this->assertEquals($vatAmount, $order->getFlowConnectorVat());
            $this->assertEquals($baseDutyAmount, $order->getFlowConnectorBaseDuty());
            $this->assertEquals($dutyAmount, $order->getFlowConnectorDuty());

            $rounding = isset($subtotalCompInfo['rounding']['amount']) ?
                $subtotalCompInfo['rounding']['amount'] : 0;

            $baseRounding = isset($subtotalCompInfo['rounding']['base']['amount']) ?
                $subtotalCompInfo['rounding']['base']['amount'] : 0;

            $this->assertEquals($baseRounding, $order->getFlowConnectorBaseRounding());
            $this->assertEquals($rounding, $order->getFlowConnectorRounding());

            $itemPrice = isset($subtotalCompInfo['item_price']['amount']) ?
                $subtotalCompInfo['item_price']['amount'] : 0;

            $baseItemPrice = isset($subtotalCompInfo['item_price']['base']['amount']) ?
                $subtotalCompInfo['item_price']['base']['amount'] : 0;

            $this->assertEquals($baseItemPrice, $order->getFlowConnectorBaseItemPrice());
            $this->assertEquals($itemPrice, $order->getFlowConnectorItemPrice());

            //Item will be tested in allocation upserted

            //Shipping Address
            $shippingAddress = $payloadOrderInfo['destination'];
            $contact = $shippingAddress['contact'];
            $this->assertEquals($contact['name']['first'], $order->getShippingAddress()->getFirstname());
            $this->assertEquals($contact['name']['last'], $order->getShippingAddress()->getLastname());
            $this->assertEquals($shippingAddress['streets'], $order->getShippingAddress()->getStreet());
            $this->assertEquals($shippingAddress['city'], $order->getShippingAddress()->getCity());
            $shippingProvince = isset($shippingAddress['province']) ? $shippingAddress['province'] : '';
            $this->assertEquals(strtoupper($shippingProvince), strtoupper($order->getShippingAddress()->getRegion()));
            $this->assertEquals($shippingAddress['postal'], $order->getShippingAddress()->getPostcode());
            $country = $this->countryFactory->create()->loadByCode($shippingAddress['country']);
            $this->assertEquals($country->getId(), $order->getShippingAddress()->getCountryId());

            //Billing Address
            $billingAddress = $payloadOrderInfo['customer']['address'];
            $this->assertEquals($billingAddress['streets'], $order->getBillingAddress()->getStreet());
            $this->assertEquals($billingAddress['city'], $order->getBillingAddress()->getCity());
            $billingProvince = isset($billingAddress['province']) ? $billingAddress['province'] : '';
            $this->assertEquals(strtoupper($billingProvince), strtoupper($order->getBillingAddress()->getRegion()));
            $this->assertEquals($billingAddress['postal'], $order->getBillingAddress()->getPostcode());
            $country = $this->countryFactory->create()->loadByCode($billingAddress['country']);
            $this->assertEquals($country->getId(), $order->getShippingAddress()->getCountryId());

        }
    }
}