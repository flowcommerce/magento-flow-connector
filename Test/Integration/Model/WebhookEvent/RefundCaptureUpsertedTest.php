<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use Fixtures\Invoice;
use FlowCommerce\FlowConnector\Exception\WebhookException;
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
use Magento\Sales\Api\OrderPaymentRepositoryInterface as OrderPaymentRepository;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection as OrderPaymentCollection;

/**
 * Test class for \FlowCommerce\FlowConnector\Model\WebhookEvent
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class RefundCaptureUpsertedTest extends \PHPUnit\Framework\TestCase
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

    /** @var OrderPaymentRepository */
    private $orderPaymentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Sets up for tests
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createWebhookEventsFixture = $this->objectManager->create(CreateWebhookEvents::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->mageOrderRepository = $this->objectManager->create(OrderRepository::class);
        $this->orderItemRepository = $this->objectManager->create(OrderItemRepository::class);
        $this->orderPaymentRepository = $this->objectManager->create(OrderPaymentRepository::class);
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
     * @throws WebhookException
     */
    public function testRefundCaptureUpserted()
    {
        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process();

        $cardAuthorizationUpsertedEvents = $this->createWebhookEventsFixture->createCardAuthorizationUpsertedWebhooks();
        $this->webhookEventManager->process();

        $captureEvents = $this->createWebhookEventsFixture->createCaptureUpsertedWebhooks();
        $this->webhookEventManager->process();

        $refundCaptureEvents = $this->createWebhookEventsFixture->createRefundCaptureUpsertedWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($refundCaptureEvents),
            $webhookCollection->count()
        );

        //Processing refund capture event
        $this->webhookEventManager->process();

        foreach ($refundCaptureEvents as $refundCaptureEvent) {
            $payload = $refundCaptureEvent->getPayloadData();

            $refundInfo = $payload['refund_capture']['refund'];

            $authorizationId = $refundInfo['authorization']['key'];

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(OrderPayment::ADDITIONAL_INFORMATION, '%'.$authorizationId.'%', 'like')
                ->create();

            /** @var OrderPaymentCollection $orders */
            $orderPayments = $this->orderPaymentRepository->getList($searchCriteria);
            $this->assertEquals(1, $orderPayments->count());

            /*$creditMemos = $order->getCreditmemosCollection();
            $this->assertEquals(1, count($creditMemos)); */

        }
        //Validate all "done" events
        $webhookCollectionNeq = $this->webhookEventCollectionFactory->create();
        $webhookCollectionNeq->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, ['neq' => WebhookEvent::STATUS_DONE]);
        $webhookCollectionNeq->load();
        $neqIds = [];
        foreach($webhookCollectionNeq as $webhookCollectionNeqItem) {
            $neqIds[] = $webhookCollectionNeqItem->getData(WebhookEvent::DATA_KEY_MESSAGE);
        }

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) +
            count($cardAuthorizationUpsertedEvents)+ count($captureEvents) + count($refundCaptureEvents),
            $webhookCollection->count(),
            implode('|', $neqIds)
        );
    }
}
