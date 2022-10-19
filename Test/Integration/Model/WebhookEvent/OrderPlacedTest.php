<?php
namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Controller\Webhooks\OrderPlaced;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory as WebhookEventCollectionFactory;
use FlowCommerce\FlowConnector\Model\SessionManager;
use FlowCommerce\FlowConnector\Model\WebhookEvent as Subject;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateWebhookEvents;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\Order\ItemRepository as OrderItemRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Test class for Test class for \FlowCommerce\FlowConnector\Model\WebhookEvent
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @package FlowCommerce\FlowConnector\Test\Integration\Model
 */
class OrderPlacedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateProductsWithCategories
     */
    private $createProductsFixture;

    /**
     * @var CreateWebhookEvents
     */
    private $createWebhookEventsFixture;

    /** @var CustomerRepository */
    private $customerRepository;

    /** @var CollectionFactory */
    private $mageOrderCollectionFactory;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

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
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createWebhookEventsFixture = $this->objectManager->create(CreateWebhookEvents::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->sessionManager = $this->objectManager->create(SessionManager::class);
        $this->productRepository = $this->objectManager->create(ProductRepository::class);
        $this->countryFactory = $this->objectManager->create(CountryFactory::class);
        $this->customerRepository = $this->objectManager->create(CustomerRepository::class);
        $this->mageOrderFactory = $this->objectManager->create(OrderFactory::class);
        $this->mageOrderCollectionFactory = $this->objectManager->create(CollectionFactory::class);
        $this->mageOrderRepository = $this->objectManager->create(OrderRepository::class);
        $this->orderItemRepository = $this->objectManager->create(OrderItemRepository::class);
        $this->webhookEventManager = $this->objectManager->create(WebhookEventManager::class);
        $this->webhookEventCollectionFactory = $this->objectManager->create(WebhookEventCollectionFactory::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $this->subject = $this->objectManager->create(Subject::class);
        $this->jsonSerializer = $this->objectManager->create(JsonSerializer::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @throws \Exception
     * @throws LocalizedException
     */
    public function testOrderPlaced()
    {
        $this->createProductsFixture->execute();
        $orderPlacedEvents = $this->createWebhookEventsFixture
            ->createOrderPlacedWebhooks();

        //Process
        $this->webhookEventManager->process();

        //Test values in magento orders
        foreach ($orderPlacedEvents as $orderPlacedEvent) {
            $payload = $orderPlacedEvent->getPayloadData();
            $flowOrderId = $payload['order']['number'];
            $trimExtOrderId = $this->subject->getTrimExtOrderId($flowOrderId);
            $searchCriteria = $this->searchCriteriaBuilder
                                   ->addFilter(Order::EXT_ORDER_ID, $trimExtOrderId, 'eq')
                                   ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);
            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            $payloadOrderInfo = $payload['order'];
            $payloadOrderLines = $payload['order']['lines'];
            $lines = [];
            foreach ($payloadOrderLines as $line) {
                $lines[$line['item_number']] = $line;
            }

            $submittedAt = isset($payloadOrderInfo['submitted_at']) ? $payloadOrderInfo['submitted_at'] : null;

            $this->assertNotNull($submittedAt);

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

            $this->assertEquals(round((float) $baseTaxAmount, 4), $order->getBaseTaxAmount());
            $this->assertEquals(round((float) $taxAmount, 4), $order->getTaxAmount());

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
            $vatAmount = 0.0;
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
                    $dutyAmount += $component['amount'];
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
                    $dutyAmount += $component['amount'];
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

            //Allocation test (items)
            $payloadAllocationInfo = $payload['allocation'];
            $allocationDetails = $payloadAllocationInfo['details'];

            $allocationItems = [];
            foreach ($allocationDetails as $detail) {
                if (($detail['key']=='subtotal') && array_key_exists('number', $detail)) {
                    $allocationItems[$detail['number']] =  $detail;
                }
            }

            //Validate order items with payload items
            $itemCount = 0;
            /** @var OrderItem $item */
            foreach ($order->getAllVisibleItems() as $item) {
                $orderItemSku = $item->getSku();
                $quantity = intval($allocationItems[$item->getSku()]['quantity']);
                $quantityOrdered = intval($item->getQtyOrdered());
                $subtotalAmounts = $this->subject->initializeSubtotalAmounts();
                $subtotalAmounts = $this->subject->allocateSubtotalAmounts($subtotalAmounts, $allocationItems[$orderItemSku]['included'], $quantity);
                $subtotalAmounts = $this->subject->allocateSubtotalAmounts($subtotalAmounts, $allocationItems[$orderItemSku]['not_included'], $quantity);
                $this->assertEquals($quantity, $quantityOrdered);
                $this->assertEquals(round((float) $subtotalAmounts['rawItemPrice'], 4), $item->getOriginalPrice());
                $this->assertEquals(round((float) $subtotalAmounts['baseRawItemPrice'], 4), $item->getBaseOriginalPrice());
                $this->assertEquals(round((float) $subtotalAmounts['itemPrice'], 4), $item->getPrice());
                $this->assertEquals(round((float) $subtotalAmounts['baseItemPrice'], 4), $item->getBasePrice());
                $this->assertEquals(round((float) $subtotalAmounts['itemPrice'] * $quantity, 4), $item->getRowTotal());
                $this->assertEquals(round((float) $subtotalAmounts['baseItemPrice'] * $quantity, 4), $item->getBaseRowTotal());
                $this->assertEquals(round((float)(($subtotalAmounts['vatPrice'] + $subtotalAmounts['dutyPrice']) * $quantity), 4), $item->getTaxAmount());
                $this->assertEquals(round((float)(($subtotalAmounts['baseVatPrice'] + $subtotalAmounts['baseDutyPrice']) * $quantity), 4), $item->getBaseTaxAmount());
                $this->assertEquals(round((float) $subtotalAmounts['itemPriceInclTax'], 4), $item->getPriceInclTax());
                $this->assertEquals(round((float) $subtotalAmounts['baseItemPriceInclTax'], 4), $item->getBasePriceInclTax());
                $this->assertEquals(round((float) $subtotalAmounts['itemPriceInclTax'] * $quantity, 4), $item->getRowTotalInclTax());
                $this->assertEquals(round((float) $subtotalAmounts['baseItemPriceInclTax'] * $quantity, 4), $item->getBaseRowTotalInclTax());
                $this->assertEquals(round((float) $subtotalAmounts['rawItemPrice'] * $quantity, 4), $item->getFlowConnectorItemPrice());
                $this->assertEquals(round((float) $subtotalAmounts['baseRawItemPrice'] * $quantity, 4), $item->getFlowConnectorBaseItemPrice());
                $this->assertEquals(round((float) $subtotalAmounts['vatPrice'] * $quantity, 4), $item->getFlowConnectorVat());
                $this->assertEquals(round((float) $subtotalAmounts['baseVatPrice'] * $quantity, 4), $item->getFlowConnectorBaseVat());
                $this->assertEquals(round((float) $subtotalAmounts['dutyPrice'] * $quantity, 4), $item->getFlowConnectorDuty());
                $this->assertEquals(round((float) $subtotalAmounts['baseDutyPrice'] * $quantity, 4), $item->getFlowConnectorBaseDuty());
                $this->assertEquals(round((float) $subtotalAmounts['roundingPrice'] * $quantity, 4), $item->getFlowConnectorRounding());
                $this->assertEquals(round((float) $subtotalAmounts['baseRoundingPrice'] * $quantity, 4), $item->getFlowConnectorBaseRounding());
                $this->assertEquals(round((float) $subtotalAmounts['vatPct'] * 100, 4), $item->getFlowConnectorVatRatePercent());
                $this->assertEquals(round((float) $subtotalAmounts['dutyPct'] * 100, 4), $item->getFlowConnectorDutyRatePercent());

                // Check if requested options were actually applied to order item
                if (isset($lines[$orderItemSku]['attributes']['options'])) {
                    $savedOptions = $item->getProductOptions();
                    $savedOptionValues = [];
                    if (isset($savedOptions['options'])) {
                        foreach ($savedOptions['options'] as $savedOption) {
                            $savedOptionValues['option_'.$savedOption['option_id']] = $savedOption['option_value'];
                        }
                        $requestedOptions = $this->jsonSerializer->unserialize($lines[$orderItemSku]['attributes']['options']);

                        foreach ($requestedOptions as $requestedOption) {
                            if (!in_array($requestedOption['code'], ['info_buyRequest','option_ids'])) {
                                $this->assertEquals(
                                    $requestedOption['value'],
                                    $savedOptionValues[$requestedOption['code']]
                                );
                            }
                        }
                    }
                }

                $itemCount++;
            }

            //Checking number of items in allocationItems and item count
            $this->assertEquals($itemCount, count($allocationItems));

            //Shipping Address
            if (isset($payloadOrderInfo['destination'])) {
                $shippingAddress = $payloadOrderInfo['destination'];

                $contact = isset($shippingAddress['contact']) ? $shippingAddress['contact'] : null;
                $this->assertEquals($contact['name']['first'], $order->getShippingAddress()->getFirstname());
                $this->assertEquals($contact['name']['last'], $order->getShippingAddress()->getLastname());

                $streets = isset($shippingAddress['streets']) ? $shippingAddress['streets'] : [''];
                $this->assertEquals($streets, $order->getShippingAddress()->getStreet());

                $city = isset($shippingAddress['city']) ? $shippingAddress['city'] : '';
                $this->assertEquals($city, $order->getShippingAddress()->getCity());

                $shippingProvince = isset($shippingAddress['province']) ? $shippingAddress['province'] : '';
                $this->assertEquals(strtoupper((string)$shippingProvince), strtoupper((string)$order->getShippingAddress()->getRegion()));

                $postal = isset($shippingAddress['postal']) ? $shippingAddress['postal'] : '';
                $this->assertEquals($postal, $order->getShippingAddress()->getPostcode());

                $country = isset($shippingAddress['country']) ? $shippingAddress['country'] : '';
                if ($country) {
                    $mageCountry = $this->countryFactory->create()->loadByCode($country);
                    $this->assertEquals($mageCountry->getId(), $order->getShippingAddress()->getCountryId());
                }
            }

            //Billing Address from payment
            // Paypal orders have no billing address on the payments entity
            $flowPayment = isset($payloadOrderInfo['payments']) ? $payloadOrderInfo['payments'] : null;
            if ((isset($flowPayment['type']) && $flowPayment['type'] == 'online') ||
                !isset($flowPayment['address'])) {
                if ($shippingAddress) {
                    $billingAddress = $shippingAddress;
                } else {
                    $billingAddress = $payloadOrderInfo['customer']['address'];
                }
            } else {
                $billingAddress = $flowPayment['address'];
            }
            if ($billingAddress) {
                $streets = isset($billingAddress['streets']) ? $billingAddress['streets'] : [''];
                $this->assertEquals($streets, $order->getBillingAddress()->getStreet());

                $city = isset($billingAddress['city']) ? $billingAddress['city'] : '';
                $this->assertEquals($city, $order->getBillingAddress()->getCity());

                $billingProvince = isset($billingAddress['province']) ? $billingAddress['province'] : '';
                $this->assertEquals(strtoupper((string)$billingProvince), strtoupper((string)$order->getBillingAddress()->getRegion()));

                $postal = isset($billingAddress['postal']) ? $billingAddress['postal'] : '';
                $this->assertEquals($postal, $order->getBillingAddress()->getPostcode());

                $country = isset($billingAddress['country']) ? $billingAddress['country'] : '';
                if ($country) {
                    $mageCountry = $this->countryFactory->create()->loadByCode($country);
                    $this->assertEquals($mageCountry->getId(), $order->getBillingAddress()->getCountryId());
                }
            }
        }
    }
}
