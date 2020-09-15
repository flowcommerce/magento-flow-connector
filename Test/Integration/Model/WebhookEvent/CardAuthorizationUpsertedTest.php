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
 * @magentoDbIsolation enabled
 */
class CardAuthorizationUpsertedTest extends \PHPUnit\Framework\TestCase
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
     * @magentoDbIsolation enabled
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws LocalizedException
     */
    public function testCardAuthorizationUpserted()
    {
        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(100, 1);
        
        $cardAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($cardAuthorizationUpsertedEvents),
            $webhookCollection->count()
        );

        //Processing card authorization upserted event
        $this->webhookEventManager->process(100, 1);

        foreach ($cardAuthorizationUpsertedEvents as $cardAuthorizationUpsertedEvent) {
            $payload = $cardAuthorizationUpsertedEvent->getPayloadData();
            $authorizationInfo = $payload['authorization'];
            $flowOrderId = $authorizationInfo['order']['number'];
            $trimExtOrderId = $this->subject->getTrimExtOrderId($flowOrderId);

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $trimExtOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            $status = $authorizationInfo['result']['status'];

            foreach ($order->getPaymentsCollection() as $payment) {
                $flowPaymentReference = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

                //Extract additional information
                $flowPaymentDescription = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_DESCRIPTION);
                $flowPaymentType = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_TYPE);

                //Authorization
                $this->assertEquals($authorizationInfo['id'], $flowPaymentReference);

                //Card information
                $cardInformation = $authorizationInfo['card'];
                $methodInformation = $authorizationInfo['method'];
                $this->assertEquals($methodInformation['type'], $flowPaymentType);
                $paymentDescription = $methodInformation['name'] . ' ' . $cardInformation['last4'];
                $this->assertEquals($paymentDescription, $flowPaymentDescription);

                //Amounts
                switch ($status) {
                    case 'pending':
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getStatus());
                        break;
                    case 'expired':
                        $this->assertEquals(0.0, $payment->getAmountAuthorized());
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getStatus());
                        break;
                    case 'authorized':
                        $this->assertEquals($authorizationInfo['amount'], $payment->getAmountAuthorized());
                        $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
                        $this->assertEquals(Order::STATE_PROCESSING, $order->getStatus());
                        break;
                    case 'review':
                        $this->assertEquals(Order::STATE_PAYMENT_REVIEW, $order->getState());
                        $this->assertEquals(Order::STATE_PAYMENT_REVIEW, $order->getStatus());
                        $this->assertEquals(null, $payment->getAmountAuthorized());
                        break;
                    case 'declined':
                        $this->assertTrue($payload->setIsFraudDetected());
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getStatus());
                        break;
                    case 'reversed':
                        $this->assertEquals(0.0, $payment->getAmountAuthorized());
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getState());
                        $this->assertEquals(Order::STATE_PENDING_PAYMENT, $order->getStatus());
                        break;
                }
            }
        }
        //Validade all "done" events
        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) + count($cardAuthorizationUpsertedEvents),
            $webhookCollection->count()
        );
    }
}
