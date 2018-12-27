<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;
use FlowCommerce\FlowConnector\Model\WebhookManager\PayloadValidator;

/**
 * Base controller to help with webhook events.
 */
abstract class Base extends \Magento\Framework\App\Action\Action
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * @var PayloadValidator
     */
    private $payloadValidator;

    /**
     * Base constructor.
     * @param Context $context
     * @param LoggerInterface $logger
     * @param ResponseInterface $response
     * @param ManagerInterface $eventManager
     * @param WebhookEventManager $webhookEventManager
     * @param PayloadValidator $payloadValidator
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ResponseInterface $response,
        ManagerInterface $eventManager,
        WebhookEventManager $webhookEventManager,
        PayloadValidator $payloadValidator
    ) {
        $this->logger = $logger;
        $this->response = $response;
        $this->eventManager = $eventManager;
        $this->webhookEventManager = $webhookEventManager;
        $this->payloadValidator = $payloadValidator;
        parent::__construct($context);
    }

    /**
    * Returns the event type for the webhook.
    */
    abstract public function getEventType();

    /**
     * Process webhook.
     *
     * @return void
     * @throws NotFoundException
     */
    public function execute()
    {
        $payload = $this->getRequest()->getContent();
        $xFlowSignatureHeader = $this->getRequest()->getHeader('X-Flow-Signature', '');

        if(!$this->payloadValidator->validate($xFlowSignatureHeader, $payload)) {
            $this->logger->warning(sprintf('X-Flow-Signature not valid for %s', $this->getEventType()));
            throw new NotFoundException(__('Page not found.'));
        }

        $storeId = $this->getRequest()->getParam('storeId');
        $this->webhookEventManager->queue($this->getEventType(), $payload, $storeId);

        // Fire an event for client extension code to process
        $eventName = WebhookEvent::EVENT_FLOW_PREFIX . $this->getEventType();
        $this->logger->info('Firing event: ' . $eventName);
        $this->eventManager->dispatch($eventName, [
            'type' => $this->getEventType(),
            'payload' => $payload,
            'storeId' => $storeId,
            'logger' => $this->logger
        ]);

        $resdata = [
            'result' => 'ok'
        ];

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($resdata);
        return $response;
    }
}
