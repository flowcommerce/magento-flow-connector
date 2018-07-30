<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

/**
 * Test class for WebhookEventManager.
 */
class WebhookEventManagerTest extends \PHPUnit\Framework\TestCase {

    const WEBHOOK_EVENT_TYPE = 'test_event_type';

    protected $logger;
    protected $jsonHelper;
    protected $util;
    protected $webhookEventFactory;
    protected $storeManager;
    protected $objectManager;
    protected $webhookEvent;
    protected $webhookEventManager;

    protected function setUp() {
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->jsonHelper->method('jsonEncode')
            ->will($this->returnCallback(function($data) {
                return json_encode($data);
            }));
        $this->jsonHelper->method('jsonDecode')
            ->will($this->returnCallback(function($data) {
                return json_decode($data, true);
            }));

        $this->util = $this->createMock(\FlowCommerce\FlowConnector\Model\Util::class);

        $this->webhookEvent = $this->createMock(\FlowCommerce\FlowConnector\Model\WebhookEvent::class);
        $this->webhookEvent->expects($this->once())->method('save');

        $this->webhookEventFactory = $this->createMock(\FlowCommerce\FlowConnector\Model\WebhookEventFactory::class);
        $this->webhookEventFactory->method('create')->willReturn($this->webhookEvent);

        $this->storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);

        $this->webhookEventManager = new \FlowCommerce\FlowConnector\Model\WebhookEventManager(
            $this->logger,
            $this->jsonHelper,
            $this->util,
            $this->webhookEventFactory,
            $this->storeManager,
            $this->objectManager
        );
    }

    /**
     * Test WebhookEventManager queue.
     */
    public function testQueue() {
        $payloadData = [
            'hello' => 'world',
            'timestamp' => '1/1/2018'
        ];
        $payload = json_encode($payloadData);
        $this->webhookEventManager->queue(self::WEBHOOK_EVENT_TYPE, $payload, 0);
    }

}
