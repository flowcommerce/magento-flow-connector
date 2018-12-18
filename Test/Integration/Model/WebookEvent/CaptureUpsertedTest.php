<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\Config\Source\InvoiceEvent;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory as WebhookEventCollectionFactory;
use FlowCommerce\FlowConnector\Model\Util as FlowUtil;
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
class CaptureUpsertedTest extends \PHPUnit\Framework\TestCase
{

    const STORE_ID = 1;
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
     * @var FlowUtil|\PHPUnit_Framework_MockObject_MockObject
     */
    private $flowUtil;

    /**
     * Sets up for tests
     * @magentoDbIsolation enabled
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createWebhookEventsFixture = $this->objectManager->create(CreateWebhookEvents::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->flowUtil = $this->createPartialMock(FlowUtil::class, ['getFlowInvoiceEvent']);
        $this->mageOrderRepository = $this->objectManager->create(OrderRepository::class);
        $this->orderItemRepository = $this->objectManager->create(OrderItemRepository::class);
        $this->webhookEventManager = $this->objectManager->create(WebhookEventManager::class);
        $this->webhookEventCollectionFactory = $this->objectManager->create(WebhookEventCollectionFactory::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $this->subject = $this->objectManager->create(Subject::class, [
            'flowUtil' => $this->flowUtil,
        ]);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @magentoDbIsolation enabled
     * @throws LocalizedException
     */
    public function testCaptureUpserted()
    {
        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(1000, 1);
        
        $cardAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        $onlineAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createOnlineAuthorizationUpsertedWebhooks(
            ['online_authorization_upserted_v2_paypal.json']
        );

        $this->webhookEventManager->process(1000, 1);

        $captureEvents = $this->createWebhookEventsFixture->createCaptureUpsertedWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($captureEvents),
            $webhookCollection->count()
        );

        //Processing card authorization upserted event
        $this->webhookEventManager->process(1000, 1);

        foreach ($captureEvents as $captureEvent) {
            $payload = $captureEvent->getPayloadData();
            $captureInfo = $payload['capture'];
            $authorizationInfo = $captureInfo['authorization'];
            $flowOrderId = $authorizationInfo['order']['number'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            if ($order->getState() == Order::STATE_PROCESSING) {
                $this->assertEquals(1, $order->getInvoiceCollection()->count());
            } else {
                $this->assertEquals(0, $order->getInvoiceCollection()->count());
            }

            foreach ($order->getPaymentsCollection() as $payment) {
                //Extract payment information
                $flowPaymentReference = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

                //Authorization
                $this->assertEquals($authorizationInfo['id'], $flowPaymentReference);

                //Transaction is closed
                if ($payment->canCapture()) {
                    $this->assertTrue($payment->hasIsTransactionClosed());
                } else {
                    $this->assertFalse($payment->hasIsTransactionClosed());
                }
            }
        }
        //Validate all "done" events
        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) +
            count($cardAuthorizationUpsertedEvents)+ count($captureEvents)+ count($onlineAuthorizationUpsertedEvents),
            $webhookCollection->count()
        );
    }

    /**
     * Test auto invoice
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/invoice_event 1
     * @throws LocalizedException
     */
    public function testAutoInvoiceWhenCapture()
    {
        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(1000, 1);
        

        $cardAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        $onlineAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createOnlineAuthorizationUpsertedWebhooks(
            ['online_authorization_upserted_v2_paypal.json']
        );

        $this->webhookEventManager->process(1000, 1);

        $captureEvents = $this->createWebhookEventsFixture->createCaptureUpsertedWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($captureEvents),
            $webhookCollection->count()
        );

        //Processing card authorization upserted event
        $this->webhookEventManager->process(1000, 1);

        foreach ($captureEvents as $captureEvent) {
            $payload = $captureEvent->getPayloadData();
            $captureInfo = $payload['capture'];
            $authorizationInfo = $captureInfo['authorization'];
            $flowOrderId = $authorizationInfo['order']['number'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            if ($order->getState() == Order::STATE_PROCESSING) {
                $this->assertEquals(1, $order->getInvoiceCollection()->count());
            } else {
                $this->assertEquals(0, $order->getInvoiceCollection()->count());
            }

            foreach ($order->getPaymentsCollection() as $payment) {
                //Extract payment information
                $flowPaymentReference = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

                //Authorization
                $this->assertEquals($authorizationInfo['id'], $flowPaymentReference);

                //Transaction is closed
                if ($payment->canCapture()) {
                    $this->assertTrue($payment->hasIsTransactionClosed());
                } else {
                    $this->assertFalse($payment->hasIsTransactionClosed());
                }
            }
        }
        //Validate all "done" events
        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) +
            count($cardAuthorizationUpsertedEvents)+ count($captureEvents)+ count($onlineAuthorizationUpsertedEvents),
            $webhookCollection->count()
        );
    }

    /**
     * Test auto invoice
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/invoice_event 2
     * @throws LocalizedException
     */
    public function testNoAutoInvoiceWhenCapture()
    {

        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(1000, 1);
        
        $cardAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        $onlineAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createOnlineAuthorizationUpsertedWebhooks(
            ['online_authorization_upserted_v2_paypal.json']
        );

        $this->webhookEventManager->process(1000, 1);

        $captureEvents = $this->createWebhookEventsFixture->createCaptureUpsertedWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($captureEvents),
            $webhookCollection->count()
        );

        //Processing card authorization upserted event
        $this->webhookEventManager->process(1000, 1);

        foreach ($captureEvents as $captureEvent) {
            $payload = $captureEvent->getPayloadData();
            $captureInfo = $payload['capture'];
            $authorizationInfo = $captureInfo['authorization'];
            $flowOrderId = $authorizationInfo['order']['number'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            //No invoices created
            $this->assertEquals(0, $order->getInvoiceCollection()->count());

            foreach ($order->getPaymentsCollection() as $payment) {
                //Extract payment information
                $flowPaymentReference = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

                //Authorization
                $this->assertEquals($authorizationInfo['id'], $flowPaymentReference);

                //Transaction is closed
                if ($payment->canCapture()) {
                    $this->assertTrue($payment->hasIsTransactionClosed());
                } else {
                    $this->assertFalse($payment->hasIsTransactionClosed());
                }
            }
        }
        //Validate all "done" events
        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) +
            count($cardAuthorizationUpsertedEvents)+ count($captureEvents)+ count($onlineAuthorizationUpsertedEvents),
            $webhookCollection->count()
        );
    }

    /**
     * Test auto invoice
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/invoice_event 0
     * @throws LocalizedException
     */
    public function testAutoInvoiceWhenCaptureDisabled()
    {
        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(1000, 1);
        
        $cardAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        $onlineAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createOnlineAuthorizationUpsertedWebhooks(
            ['online_authorization_upserted_v2_paypal.json']
        );

        $this->webhookEventManager->process(1000, 1);

        $captureEvents = $this->createWebhookEventsFixture->createCaptureUpsertedWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($captureEvents),
            $webhookCollection->count()
        );

        //Processing card authorization upserted event
        $this->webhookEventManager->process(1000, 1);

        foreach ($captureEvents as $captureEvent) {
            $payload = $captureEvent->getPayloadData();
            $captureInfo = $payload['capture'];
            $authorizationInfo = $captureInfo['authorization'];
            $flowOrderId = $authorizationInfo['order']['number'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            $this->assertEquals(0, $order->getInvoiceCollection()->count());

            foreach ($order->getPaymentsCollection() as $payment) {
                //Extract payment information
                $flowPaymentReference = $payment->getAdditionalInformation(WebhookEvent::FLOW_PAYMENT_REFERENCE);

                //Authorization
                $this->assertEquals($authorizationInfo['id'], $flowPaymentReference);

                //Transaction is closed
                if ($payment->canCapture()) {
                    $this->assertTrue($payment->hasIsTransactionClosed());
                } else {
                    $this->assertFalse($payment->hasIsTransactionClosed());
                }
            }
        }
        //Validate all "done" events
        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) +
            count($cardAuthorizationUpsertedEvents)+ count($captureEvents)+ count($onlineAuthorizationUpsertedEvents),
            $webhookCollection->count()
        );
    }

    /**
     * Test send email on invoice
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/invoice_event 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/invoice_email 1
     * @throws LocalizedException
     */
    public function testAutoInvoiceSendEmailTrue()
    {
        $this->createProductsFixture->execute();

        $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(1000, 1);
        
        $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        //Using only the authorized file
        $this->createWebhookEventsFixture->createOnlineAuthorizationUpsertedWebhooks(
            ['online_authorization_upserted_v2_paypal.json']
        );

        $this->webhookEventManager->process(1000, 1);

        $captureEvents = $this->createWebhookEventsFixture->createCaptureUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        foreach ($captureEvents as $captureEvent) {
            $payload = $captureEvent->getPayloadData();
            $captureInfo = $payload['capture'];
            $authorizationInfo = $captureInfo['authorization'];
            $flowOrderId = $authorizationInfo['order']['number'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            /** @var Order $order */
            $order = $orders->getFirstItem();

            foreach ($order->getInvoiceCollection() as $invoice) {
                //TODO: Uncomment assert when email sending is fixed
                //$this->assertTrue((bool) $invoice->getEmailSent());
            }
        }
    }

    /**
     * Test don't send email on invoice
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/invoice_event 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/invoice_email 0
     * @throws LocalizedException
     */
    public function testAutoInvoiceSendEmailFalse()
    {
        $this->createProductsFixture->execute();

        $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        //Using only the authorized file
        $this->createWebhookEventsFixture->createOnlineAuthorizationUpsertedWebhooks(
            ['online_authorization_upserted_v2_paypal.json']
        );

        $this->webhookEventManager->process(1000, 1);

        $captureEvents = $this->createWebhookEventsFixture->createCaptureUpsertedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        foreach ($captureEvents as $captureEvent) {
            $payload = $captureEvent->getPayloadData();
            $captureInfo = $payload['capture'];
            $authorizationInfo = $captureInfo['authorization'];
            $flowOrderId = $authorizationInfo['order']['number'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            /** @var Order $order */
            $order = $orders->getFirstItem();

            foreach ($order->getInvoiceCollection() as $invoice) {
                $this->assertFalse((bool)$invoice->getEmailSent());
            }
        }
    }
}
