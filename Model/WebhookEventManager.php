<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\WebhookEventManagementInterface;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent as WebhookEventResourceModel;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory as WebhookEventCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class WebhookEventManager
 * @package FlowCommerce\FlowConnector\Model
 */
class WebhookEventManager implements WebhookEventManagementInterface
{
    /**
     * Number of seconds to delay before reprocessing
     */
    const REQUEUE_DELAY_INTERVAL = 30;

    /**
     * Maximum age in seconds for a WebhookEvent to requeue
     */
    const REQUEUE_MAX_AGE = 3600;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var WebhookEventCollectionFactory
     */
    private $webhookEventCollectionFactory;

    /**
     * @var WebhookEventFactory
     */
    private $webhookEventFactory;

    /**
     * @var WebhookEventResourceModel
     */
    private $webhookEventResourceModel;

    /**
     * WebhookEventManager constructor.
     * @param Notification $notification
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param WebhookEventCollectionFactory $webhookEventCollectionFactory
     * @param WebhookEventFactory $webhookEventFactory
     * @param WebhookEventResourceModel $webhookEventResourceModel
     */
    public function __construct(
        Notification $notification,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        WebhookEventCollectionFactory $webhookEventCollectionFactory,
        WebhookEventFactory $webhookEventFactory,
        WebhookEventResourceModel $webhookEventResourceModel
    ) {
        $this->notification = $notification;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->webhookEventCollectionFactory = $webhookEventCollectionFactory;
        $this->webhookEventFactory = $webhookEventFactory;
        $this->webhookEventResourceModel = $webhookEventResourceModel;
    }

    /**
     * Set the logger (used by console command).
     * @param Logger $logger
     * @return void
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        $this->notification->setLogger($logger);
    }

    /**
     * Queue webhook event for processing.
     * @param string $type
     * @param string[] $payload
     * @param int $storeId
     * @return WebhookEvent
     * @throws \Exception
     */
    public function queue($type, $payload, $storeId)
    {
        $this->logger->info('Queue webhook event type: ' . $type);

        $payloadData = $this->jsonSerializer->unserialize($payload);
        $timestamp = $payloadData['timestamp'];

        $webhookEvent = $this->webhookEventFactory->create();
        $webhookEvent->setType($type);
        $webhookEvent->setPayload($payload);
        $webhookEvent->setStoreId($storeId);
        $webhookEvent->setStatus(WebhookEvent::STATUS_NEW);
        $webhookEvent->setTriggeredAt($timestamp);
        $this->saveWebhookEvent($webhookEvent);
        return $webhookEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function processAll()
    {
        $stillProcessing = true; 
        do {
            $stillProcesing = $this->process();
        } while ($stillProcessing);
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->logger->info('Starting webhook event processing');

        $this->deleteOldProcessedEvents();
        $this->resetOldErrorEvents();

        $webhookEvents = $this->getNextUnprocessedEvents();
        if ((int) count($webhookEvents) === 0) {
            $this->logger->info('No webhook events to process.');
            return false;
        }

        foreach ($webhookEvents as $webhookEvent) {
            $this->logger->info('Processing webhook event: ' . $webhookEvent->getType());
            $webhookEvent->process();
        }
        $this->logger->info('Done processing webhook events');
        return true;
    }

    /**
     * Returns the next unprocessed event.
     * @return WebhookEvent[]
     */
    private function getNextUnprocessedEvents()
    {
        $collection = $this->webhookEventCollectionFactory->create();
        $collection->addFieldToFilter('status', WebhookEvent::STATUS_NEW);
        $collection->addFieldToFilter('triggered_at', ['lteq' => (new \DateTime())->format('Y-m-d H:i:s')]);
        $collection->setOrder('updated_at', 'ASC');
        $collection->setOrder('id', 'ASC');
        $collection->setPageSize(100);
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldProcessedEvents()
    {
        $this->webhookEventResourceModel->deleteOldProcessedEvents();
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    public function markPendingWebhookEventsAsDoneForOrderAndType($flowOrderId, $type)
    {
        $collection = $this->webhookEventCollectionFactory->create();
        $collection->addFieldToFilter('status', ['in' => [
            WebhookEvent::STATUS_NEW,
            WebhookEvent::STATUS_PROCESSING,
        ]]);
        $collection->addFieldToFilter('payload', ['like' => '%' . $flowOrderId . '%']);
        $collection->addFieldToFilter('type', ['eq' => $type]);
        if ($collection->getSize() > 0) {
            /** @var WebhookEvent[] $events */
            $webhookEvents = $collection->getItems();
            $this->webhookEventResourceModel
                ->updateMultipleStatuses($webhookEvents, WebhookEvent::STATUS_DONE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function markWebhookEventAsDone(WebhookEvent $webHookEvent, $message = null)
    {
        if ($message !== null) {
            $webHookEvent->setMessage((string)$message);
        }
        $webHookEvent->setStatus(WebhookEvent::STATUS_DONE);
        $this->saveWebhookEvent($webHookEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function markWebhookEventAsError(WebhookEvent $webHookEvent, $errorMessage = null)
    {
        $webHookEvent->setStatus(WebhookEvent::STATUS_ERROR);
        $webHookEvent->setMessage((string)$errorMessage);
        $this->saveWebhookEvent($webHookEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function markWebhookEventAsProcessing(WebhookEvent $webHookEvent)
    {
        $webHookEvent->setStatus(WebhookEvent::STATUS_PROCESSING);
        $this->saveWebhookEvent($webHookEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function resetOldErrorEvents()
    {
        $this->webhookEventResourceModel->resetOldErrorEvents();
    }

    /**
     * {@inheritdoc}
     */
    public function requeue(WebhookEvent $webHookEvent, $message, $force = false)
    {
        $interval = \DateInterval::createFromDateString(self::REQUEUE_MAX_AGE . ' seconds');
        $createdAt = \DateTime::createFromFormat('Y-m-d H:i:s', $webHookEvent->getCreatedAt());
        $triggeredAt = \DateTime::createFromFormat('Y-m-d H:i:s', $webHookEvent->getTriggeredAt());
        if ($createdAt->add($interval) < $triggeredAt && !$force) {
            $this->markWebhookEventAsError(
                $webHookEvent,
                'REQUEUE_MAX_AGE of ' . self::REQUEUE_MAX_AGE . ' seconds reached'
            );
        } else {
            $date = new \DateTime();
            $date->add(\DateInterval::createFromDateString(self::REQUEUE_DELAY_INTERVAL . ' seconds'));
            $webHookEvent->setTriggeredAt($date->format('Y-m-d H:i:s'));
            $webHookEvent->setMessage($message);
            $webHookEvent->setStatus(WebhookEvent::STATUS_NEW);
            $this->saveWebhookEvent($webHookEvent);
        }
    }

    /**
     * Persists given webhook event to the database through the resource model
     * @param WebhookEvent $webhookEvent
     * @return void
     */
    private function saveWebhookEvent(WebhookEvent $webhookEvent)
    {
        $this->webhookEventResourceModel->directQuerySave($webhookEvent);
    }
}
