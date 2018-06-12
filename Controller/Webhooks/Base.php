<?php

namespace FlowCommerce\FlowConnector\Controller\Webhooks;

use Magento\Framework\Controller\ResultFactory;
use FlowCommerce\FlowConnector\Model\WebhookEvent;

/**
 * Base controller to help with webhook events.
 */
abstract class Base extends \Magento\Framework\App\Action\Action {

    protected $logger;
    protected $response;
    protected $eventManager;
    protected $webhookEventManager;

    /**
    * @param \Magento\Framework\App\Action\Context $context
    * @param \Psr\Log\LoggerInterface $logger
    */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \FlowCommerce\FlowConnector\Model\WebhookEventManager $webhookEventManager
    ) {
        $this->logger = $logger;
        $this->response = $response;
        $this->eventManager = $eventManager;
        $this->webhookEventManager = $webhookEventManager;
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
    */
    public function execute()
    {
        $payload = $this->getRequest()->getContent();
        $this->webhookEventManager->queue($this->getEventType(), $payload);

        // Fire an event for client extension code to process
        $eventName = WebhookEvent::EVENT_FLOW_PREFIX . $this->getEventType();
        $this->logger->info('Firing event: ' . $eventName);
        $this->eventManager->dispatch($eventName, [
            'type' => $this->getEventType(),
            'payload' => $payload,
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
