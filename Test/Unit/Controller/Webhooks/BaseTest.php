<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Controller;

use FlowCommerce\FlowConnector\Model\Configuration;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use FlowCommerce\FlowConnector\Model\WebhookManager\PayloadValidator;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Test class for webhook controllers.
 */
class BaseTest extends \PHPUnit\Framework\TestCase
{ 
    public function testCaptureUpsertedV2()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\CaptureUpsertedV2');
        $controller->execute();
    }

    public function testCardAuthorizationUpsertedV2()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\CardAuthorizationUpsertedV2');
        $controller->execute();
    }

    public function testFraudStatusChanged()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\FraudStatusChanged');
        $controller->execute();
    }

    public function testOnlineAuthorizationUpsertedV2()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\OnlineAuthorizationUpsertedV2');
        $controller->execute();
    }

    public function testOrderPlaced()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\OrderPlaced');
        $controller->execute();
    }

    public function testRefundCaptureUpsertedV2()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\RefundCaptureUpsertedV2');
        $controller->execute();
    }

    public function testRefundUpsertedV2()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\RefundUpsertedV2');
        $controller->execute();
    }

    public function testTrackingLabelEventUpserted()
    {
        $controller = $this->getController('\FlowCommerce\FlowConnector\Controller\Webhooks\TrackingLabelEventUpserted');
        $controller->execute();
    }

    private function getController($className)
    {
        $reflection = new \ReflectionClass($className);

        $logger = $this->createMock(LoggerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams', 'getParam', 'isAjax', 'getPostValue', 'getContent', 'getHeader'])
            ->getMockForAbstractClass();

        $resultJson = $this->createMock(Json::class);

        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->method('create')->willReturn($resultJson);

        $context = $this->createMock(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getResultFactory')->willReturn($resultFactory);

        $eventManager = $this->createMock(ManagerInterface::class);
        $eventManager->expects($this->once())->method('dispatch');

        $webhookEventManager = $this->createMock(WebhookEventManager::class);
        $webhookEventManager->expects($this->once())
            ->method('queue')
            ->with($this->equalTo($reflection->getConstants()['EVENT_TYPE']));

        $payloadValidator = $this->createMock(PayloadValidator::class);
        $payloadValidator->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('isWebhookValidationEnabled')
            ->willReturn(true);

        $controller = $reflection->newInstanceArgs([
            $context,
            $logger,
            $response,
            $eventManager,
            $webhookEventManager,
            $payloadValidator,
            $configuration
        ]);

        return $controller;
    }
}
