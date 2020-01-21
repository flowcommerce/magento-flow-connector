<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\Configuration;
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
use Magento\Sales\Model\Order\Shipment as OrderShipment;

/**
 * Test class for \FlowCommerce\FlowConnector\Model\WebhookEvent
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class LabelUpsertedTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var CreateProductsWithCategories
     */
    private $createProductsFixture;

    /**
     * @var CreateWebhookEvents
     */
    private $createWebhookEventsFixture;

    /** @var Configuration */
    private $configuration;

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
        $this->configuration = $this->createPartialMock(Configuration::class, ['getFlowInvoiceEvent']);
        $this->mageOrderRepository = $this->objectManager->create(OrderRepository::class);
        $this->orderItemRepository = $this->objectManager->create(OrderItemRepository::class);
        $this->webhookEventManager = $this->objectManager->create(WebhookEventManager::class);
        $this->webhookEventCollectionFactory = $this->objectManager->create(WebhookEventCollectionFactory::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $this->subject = $this->objectManager->create(Subject::class, [
            'configuration' => $this->configuration,
        ]);
    }

    /**
     * @magentoDbIsolation enabled
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws LocalizedException
     */
    public function testLabelUpserted()
    {
        $this->createProductsFixture->execute();

        $orderPlacedEvents = $this->createWebhookEventsFixture->createOrderPlacedWebhooks();
        $this->webhookEventManager->process(1000, 1);

        $labelUpsertedEvents = $this->createWebhookEventsFixture->createLabelUpsertedWebhooks();

        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_NEW);
        $webhookCollection->load();
        $this->assertEquals(
            count($labelUpsertedEvents),
            $webhookCollection->count()
        );

        //Processing fraud status change event
        $this->webhookEventManager->process(1000, 1);

        foreach ($labelUpsertedEvents as $labelUpsertedEvent) {
            $payload = $labelUpsertedEvent->getPayloadData();
            $flowOrderId = $payload['order'];
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(Order::EXT_ORDER_ID, $flowOrderId, 'eq')
                ->create();
            /** @var OrderCollection $orders */
            $orders = $this->mageOrderRepository->getList($searchCriteria);

            $this->assertEquals(1, $orders->count());

            /** @var Order $order */
            $order = $orders->getFirstItem();

            $shipmentCollections = $order->getShipmentsCollection();
            $this->assertEquals(1, $shipmentCollections->count());

            //Assuming only one shipment
            /** @var OrderShipment $shipment */
            $shipment = $shipmentCollections->getFirstItem();
            foreach ($shipment->getTracks() as $track) {
                if ($track->getTitle()==WebhookEvent::FLOW_TRACK_TITLE) {
                    $this->assertEquals($payload['flow_tracking_number'], $track->getTrackNumber());
                } else {
                    $this->assertEquals($payload['carrier_tracking_number'], $track->getTrackNumber());
                }
            }
        }

        //Validade all "done" events
        $webhookCollection = $this->webhookEventCollectionFactory->create();
        $webhookCollection->addFieldToFilter(WebhookEvent::DATA_KEY_STATUS, WebhookEvent::STATUS_DONE);
        $webhookCollection->load();
        $this->assertEquals(
            count($orderPlacedEvents) + count($labelUpsertedEvents),
            $webhookCollection->count()
        );
    }
}
