<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\Notification;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use FlowCommerce\FlowConnector\Model\WebhookEventFactory;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Psr\Log\LoggerInterface;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent as WebhookEventResourceModel;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory as WebhookEventCollectionFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\ObjectManagerInterface;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\Collection as WebhookEventCollection;

/**
 * Test class for WebhookEventManager.
 */
class WebhookEventManagerTest extends \PHPUnit\Framework\TestCase {

    const WEBHOOK_EVENT_TYPE = 'test_event_type';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var WebhookEventFactory
     */
    private $webhookEventFactory;

    /**
     * @var WebhookEvent
     */
    private $webhookEvent;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * @var WebhookEventResourceModel
     */
    private $webhookEventResourceModel;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var WebhookEventCollectionFactory
     */
    private $webhookEventCollectionFactory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;


    /**
     * @var WebhookEventCollection
     */
    private $webhookEventCollection;

    protected function setUp()
    {

        $this->notification = $this->createMock(Notification::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->jsonSerializer = $this->createMock(JsonSerializer::class);
        $this->jsonSerializer->method('serialize')
            ->will($this->returnCallback(function ($data) {
                return json_encode($data);
            }));
        $this->jsonSerializer->method('unserialize')
            ->will($this->returnCallback(function ($data) {
                return json_decode($data, true);
            }));

        $this->webhookEvent = $this->createMock(WebhookEvent::class);
        $this->webhookEvent->expects($this->once())->method('save');

        $this->webhookEventCollection = $this->createMock(WebhookEventCollection::class);

        $this->webhookEventCollectionFactory = $this->createMock(WebhookEventCollectionFactory::class);
        $this->webhookEventCollectionFactory->method('create')->willReturn($this->webhookEventCollection);

        $this->webhookEventFactory = $this->createMock(WebhookEventFactory::class);
        $this->webhookEventFactory->method('create')->willReturn($this->webhookEvent);

        $this->webhookEventResourceModel = $this->createMock(WebhookEventResourceModel::class);

        $this->objectManager = $this->createMock(ObjectManagerInterface::class);

        $this->webhookEventManager = new WebhookEventManager(
            $this->notification,
            $this->jsonSerializer,
            $this->logger,
            $this->webhookEventCollectionFactory,
            $this->webhookEventFactory,
            $this->webhookEventResourceModel
        );
    }

    /**
     * Test WebhookEventManager queue.
     */
    public function testQueue()
    {
        $payloadData = [
            'hello' => 'world',
            'timestamp' => '1/1/2018'
        ];
        $payload = $this->jsonSerializer->serialize($payloadData);
        $this->webhookEventManager->queue(self::WEBHOOK_EVENT_TYPE, $payload, 0);
    }

}
