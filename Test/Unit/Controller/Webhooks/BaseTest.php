<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Controller;

/**
 * Test class for webhook controllers.
 */
class BaseTest extends \PHPUnit\Framework\TestCase {

    public function testAllocationDeletedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\AllocationDeletedV2');
        $controller->execute();
    }

    public function testAllocationUpserteedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\AllocationUpsertedV2');
        $controller->execute();
    }

    public function testAuthorizationDeletedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\AuthorizationDeletedV2');
        $controller->execute();
    }

    public function testCaptureUpsertedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\CaptureUpsertedV2');
        $controller->execute();
    }

    public function testCardAuthorizationUpsertedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\CardAuthorizationUpsertedV2');
        $controller->execute();
    }

    public function testFraudStatusChanged() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\FraudStatusChanged');
        $controller->execute();
    }

    public function testOnlineAuthorizationUpsertedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\OnlineAuthorizationUpsertedV2');
        $controller->execute();
    }

    public function testOrderDeletedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\OrderDeletedV2');
        $controller->execute();
    }

    public function testOrderUpsertedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\OrderUpsertedV2');
        $controller->execute();
    }

    public function testRefundCaptureUpsertedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\RefundCaptureUpsertedV2');
        $controller->execute();
    }

    public function testRefundUpsertedV2() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\RefundUpsertedV2');
        $controller->execute();
    }

    public function testTrackingLabelEventUpserted() {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\TrackingLabelEventUpserted');
        $controller->execute();
    }

    private function getController($className) {
        $reflection = new \ReflectionClass($className);

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $response = $this->createMock(\Magento\Framework\App\ResponseInterface::class);

        $request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams', 'getParam', 'isAjax', 'getPostValue', 'getContent'])
            ->getMockForAbstractClass();

        $resultJson = $this->createMock(\Magento\Framework\Controller\Result\Json::class);

        $resultFactory = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $resultFactory->method('create')->willReturn($resultJson);

        $context = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $eventManager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $eventManager->expects($this->once())->method('dispatch');

        $webhookEventManager = $this->createMock(\FlowCommerce\FlowConnector\Model\WebhookEventManager::class);
        $webhookEventManager->expects($this->once())
            ->method('queue')
            ->with($this->equalTo($reflection->getConstants()['EVENT_TYPE']));

        $controller = $reflection->newInstanceArgs([
            $context,
            $logger,
            $response,
            $eventManager,
            $webhookEventManager
        ]);

        return $controller;
    }

}
