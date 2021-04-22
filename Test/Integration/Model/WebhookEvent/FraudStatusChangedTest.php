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
use Magento\Sales\Model\Order\ItemRepository as OrderItemRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;

/**
 * Test class for \FlowCommerce\FlowConnector\Model\WebhookEvent
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class FraudStatusChanged extends \PHPUnit\Framework\TestCase
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
    public function setUp(): void
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
    public function testFraudStatusChangedV2()
    {
        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process();
        
        $fraudStatusChangedEvents = $this->createWebhookEventsFixture->createFraudStatusChangeWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($fraudStatusChangedEvents),
            $webhookCollection->count()
        );

        //Processing fraud status change event
        $this->webhookEventManager->process();

        foreach ($fraudStatusChangedEvents as $fraudStatusChangedEvent) {
            $payload = $fraudStatusChangedEvent->getPayloadData();
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
            $status = $payload['status'];

            switch ($status) {
                case 'pending':
                    $this->assertEquals(Order::STATE_PAYMENT_REVIEW, $order->getState());
                    break;
                case 'approved':
                    $this->assertEquals(Order::STATE_PROCESSING, $order->getState());
                    break;
                case 'declined':
                    $this->assertEquals(Order::STATE_CANCELED, $order->getState());
                    break;
                case 'review':
                    break;
            }
        }

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) + count($fraudStatusChangedEvents),
            $webhookCollection->count()
        );
    }
}
