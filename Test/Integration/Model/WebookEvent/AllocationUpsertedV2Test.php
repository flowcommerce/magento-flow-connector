<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory as WebhookEventCollectionFactory;
use FlowCommerce\FlowConnector\Model\WebhookEvent as Subject;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateWebhookEvents;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\ItemRepository as OrderItemRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;

/**
 * Test class for \FlowCommerce\FlowConnector\Model\WebhookEvent
 * @magentoAppIsolation enabled
 */
class AllocationUpsertedV2Test extends \PHPUnit\Framework\TestCase
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
     * Sets up for tests
     * @magentoDbIsolation enabled
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createWebhookEventsFixture = $this->objectManager->create(CreateWebhookEvents::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->mageOrderRepository = $this->objectManager->create(OrderRepository::class);
        $this->orderItemRepository = $this->objectManager->create(OrderItemRepository::class);
        $this->webhookEventManager = $this->objectManager->create(WebhookEventManager::class);
        $this->webhookEventCollectionFactory = $this->objectManager->create(WebhookEventCollectionFactory::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $this->subject = $this->objectManager->create(Subject::class);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @magentoDbIsolation enabled
     * @throws LocalizedException
     */
    public function testAllocationUpsertedV2()
    {
        $this->createProductsFixture->execute();
        $orderUpsertedEvents = $this->createWebhookEventsFixture->createOrderUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        $allocationUpsertedEvents = $this->createWebhookEventsFixture->createAllocationUpsertedWebhooks();
        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($allocationUpsertedEvents),
            $webhookCollection->count()
        );

        $this->webhookEventManager->process(1000, 1);

        foreach ($allocationUpsertedEvents as $allocationUpsertedEvent) {
            $payload = $allocationUpsertedEvent->getPayloadData();
            $flowOrderId = $payload['allocation']['order']['number'];
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            $subtotalDetailIndex = array_search(
                'subtotal',
                array_column($payload['allocation']['details'], 'key')
            );
            $subtotalDetail = $payload['allocation']['details'][$subtotalDetailIndex];

            if (array_key_exists('number', $subtotalDetail)) {
                $sku = $subtotalDetail['number'];

                /** @var OrderItem $orderItem */
                $item = null;
                foreach ($order->getAllVisibleItems() as $orderItem) {
                    if ($orderItem->getSku() == $sku) {
                        $item = $orderItem;
                        break;
                    }
                }

                $this->assertNotNull($item);

                $quantity = $subtotalDetail['quantity'];
                $itemPrice = 0.0;
                $baseItemPrice = 0.0;
                $rawItemPrice = 0.0;
                $baseRawItemPrice = 0.0;
                $vatPct = 0.0;
                $vatPrice = 0.0;
                $baseVatPrice = 0.0;
                $dutyPct = 0.0;
                $dutyPrice = 0.0;
                $baseDutyPrice = 0.0;
                $roundingPrice = 0.0;
                $baseRoundingPrice = 0.0;
                $itemPriceInclTax = 0.0;
                $baseItemPriceInclTax = 0.0;
                $discountPrice = 0.0;
                $baseDiscountPrice = 0.0;
                $itemDiscountAmount = 0.0;
                $itemBaseDiscountAmount = 0.0;

                foreach ($subtotalDetail['included'] as $included) {
                    if ($included['key'] == 'item_price') {
                        $rawItemPrice += $included['price']['amount'];
                        $baseRawItemPrice += $included['price']['base']['amount'];
                        $itemPrice += $included['price']['amount'];
                        $baseItemPrice += $included['price']['base']['amount'];
                        $itemPriceInclTax += $included['price']['amount'];
                        $baseItemPriceInclTax += $included['price']['base']['amount'];
                    } elseif ($included['key'] == 'rounding') {
                        $itemPrice += $included['price']['amount'];
                        $baseItemPrice += $included['price']['base']['amount'];
                        $itemPriceInclTax += $included['price']['amount'];
                        $baseItemPriceInclTax += $included['price']['base']['amount'];
                        $roundingPrice += $included['price']['amount'];
                        $baseRoundingPrice += $included['price']['base']['amount'];
                    } elseif ($included['key'] == 'vat_item_price') {
                        $itemPriceInclTax += $included['price']['amount'];
                        $baseItemPriceInclTax += $included['price']['base']['amount'];
                        $vatPct += $included['rate'];
                        $vatPrice += $included['price']['amount'];
                        $baseVatPrice += $included['price']['base']['amount'];
                    } elseif ($included['key'] == 'duties_item_price') {
                        $itemPriceInclTax += $included['price']['amount'];
                        $baseItemPriceInclTax += $included['price']['base']['amount'];
                        $dutyPct += $included['rate'];
                        $dutyPrice += $included['price']['amount'];
                        $baseDutyPrice += $included['price']['base']['amount'];
                    } elseif ($included['key'] == 'item_discount') {
                        $itemDiscountAmount = $included['price']['amount'];
                        $itemBaseDiscountAmount = $included['price']['base']['amount'];
                    }
                }

                $this->assertEquals($item->getQtyOrdered(), $quantity);
                $this->assertEquals($itemPrice, $item->getOriginalPrice());
                $this->assertEquals($baseItemPrice, $item->getBaseOriginalPrice());
                $this->assertEquals($itemPrice, $item->getPrice());
                $this->assertEquals($baseItemPrice, $item->getBasePrice());
                $this->assertEquals($itemPrice * $quantity, $item->getRowTotal());
                $this->assertEquals($baseItemPrice * $quantity, $item->getBaseRowTotal());
                $this->assertEquals(($vatPct * 100) + ($dutyPct * 100), $item->getTaxPercent());
                $this->assertEquals(($vatPrice + $dutyPrice) * $quantity, $item->getTaxAmount());
                $this->assertEquals(($baseVatPrice + $baseDutyPrice) * $quantity, $item->getBaseTaxAmount());
                $this->assertEquals($itemPriceInclTax, $item->getPriceInclTax());
                $this->assertEquals($baseItemPriceInclTax, $item->getBasePriceInclTax());
                $this->assertEquals($itemPriceInclTax * $quantity, $item->getRowTotalInclTax());
                $this->assertEquals($baseItemPriceInclTax * $quantity, $item->getBaseRowTotalInclTax());
                $this->assertEquals($rawItemPrice * $quantity, $item->getFlowConnectorItemPrice());
                $this->assertEquals($baseRawItemPrice * $quantity, $item->getFlowConnectorBaseItemPrice());
                $this->assertEquals($vatPrice * $quantity, $item->getFlowConnectorVat());
                $this->assertEquals($baseVatPrice * $quantity, $item->getFlowConnectorBaseVat());
                $this->assertEquals($dutyPrice * $quantity, $item->getFlowConnectorDuty());
                $this->assertEquals($baseDutyPrice * $quantity, $item->getFlowConnectorBaseDuty());
                $this->assertEquals($roundingPrice * $quantity, $item->getFlowConnectorRounding());
                $this->assertEquals($baseRoundingPrice * $quantity, $item->getFlowConnectorBaseRounding());
                $this->assertEquals(round($itemDiscountAmount, 4), $item->getDiscountAmount());
                $this->assertEquals(round($itemBaseDiscountAmount, 4), $item->getBaseDiscountAmount());
            }
        }

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderUpsertedEvents) + count($allocationUpsertedEvents),
            $webhookCollection->count()
        );
    }
}
