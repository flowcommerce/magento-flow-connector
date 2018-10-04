<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\WebhookEventManagementInterface;
use FlowCommerce\FlowConnector\Model\Util as FlowUtil;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent as WebhookEventResourceModel;
use FlowCommerce\FlowConnector\Model\ResourceModel\WebhookEvent\CollectionFactory as WebhookEventCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\UrlInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Psr\Log\LoggerInterface as Logger;
use Zend\Http\Request;

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
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var FlowUtil
     */
    private $util;

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
     * @param FlowUtil $util
     * @param JsonSerializer $jsonSerializer
     * @param Logger $logger
     * @param StoreManager $storeManager
     * @param WebhookEventCollectionFactory $webhookEventCollectionFactory
     * @param WebhookEventFactory $webhookEventFactory
     * @param WebhookEventResourceModel $webhookEventResourceModel
     */
    public function __construct(
        FlowUtil $util,
        JsonSerializer $jsonSerializer,
        Logger $logger,
        StoreManager $storeManager,
        WebhookEventCollectionFactory $webhookEventCollectionFactory,
        WebhookEventFactory $webhookEventFactory,
        WebhookEventResourceModel $webhookEventResourceModel
    ) {
        $this->util = $util;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
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
        $this->util->setLogger($logger);
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
     * Process the webhook event queue.
     * @param $numToProcess - Number of records to process.
     * @param $keepAlive - Number of seconds to keep alive after/between processing.
     * @throws LocalizedException
     */
    public function process($numToProcess = 1000, $keepAlive = 60)
    {
        $this->logger->info('Starting webhook event processing');

        $this->deleteOldProcessedEvents();
        $this->resetOldErrorEvents();

        while ($keepAlive > 0) {
            while ($numToProcess > 0) {
                $webhookEvent = $this->getNextUnprocessedEvent();
                if ($webhookEvent == null) {
                    break;
                }

                $this->logger->info('Processing webhook event: ' . $webhookEvent->getType());
                $webhookEvent->process();

                $numToProcess -= 1;
            }

            // $this->logger->info('Webhook keep alive remaining: ' . $keepAlive);
            $keepAlive -= 1;
            sleep(1);
        }

        $this->logger->info('Done processing webhook events');
    }

    /**
     * Registers webhooks with Flow.
     * @param $storeId - ID of store
     * @return bool
     * @throws \Exception
     */
    public function registerWebhooks($storeId)
    {
        if (!$this->util->isFlowEnabled($storeId)) {
            throw new \Exception('Flow module is disabled.');
        }

        $this->deleteAllWebhooks($storeId);
        $this->registerWebhook($storeId, 'allocationdeletedv2', 'allocation_deleted_v2');
        $this->registerWebhook($storeId, 'allocationupsertedv2', 'allocation_upserted_v2');
        $this->registerWebhook($storeId, 'authorizationdeletedv2', 'authorization_deleted_v2');
        $this->registerWebhook($storeId, 'authorizationupserted', 'authorization_upserted');
        $this->registerWebhook($storeId, 'captureupsertedv2', 'capture_upserted_v2');
        $this->registerWebhook($storeId, 'cardauthorizationupsertedv2', 'card_authorization_upserted_v2');
        $this->registerWebhook($storeId, 'onlineauthorizationupsertedv2', 'online_authorization_upserted_v2');
        $this->registerWebhook($storeId, 'orderdeleted', 'order_deleted');
        $this->registerWebhook($storeId, 'orderupserted', 'order_upserted');
        $this->registerWebhook($storeId, 'refundcaptureupsertedv2', 'refund_capture_upserted_v2');
        $this->registerWebhook($storeId, 'refundupsertedv2', 'refund_upserted_v2');
        $this->registerWebhook($storeId, 'fraudstatuschanged', 'fraud_status_changed');
        $this->registerWebhook($storeId, 'trackinglabeleventupserted', 'tracking_label_event_upserted');
        $this->registerWebhook($storeId, 'labelupserted', 'label_upserted');
        return true;
    }

    /**
     * Delete all Flow connector webhooks.
     * @param $storeId - ID of store
     * @return void
     */
    private function deleteAllWebhooks($storeId)
    {
        $webhooks = $this->getRegisteredWebhooks($storeId);
        foreach ($webhooks as $webhook) {
            if (strpos($webhook['url'], '/flowconnector/webhooks/') &&
                strpos($webhook['url'], 'storeId=' . $storeId)) {
                $this->logger->info('Deleting webhook: ' . $webhook['url']);
                $client = $this->util->getFlowClient('/webhooks/' . $webhook['id'], $storeId);
                $client->setMethod(Request::METHOD_DELETE);
                $client->send();
            }
        }
    }

    /**
     * Registers a webhook with Flow.
     * @param $storeId - ID of store
     * @param $endpointStub
     * @param $event
     * @throws NoSuchEntityException
     */
    private function registerWebhook($storeId, $endpointStub, $event)
    {
        $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        $data = [
            'url' => $baseUrl . 'flowconnector/webhooks/' . $endpointStub . '?storeId=' . $storeId,
            'events' => $event,
        ];

        $this->logger->info('Registering webhook event ' . $event . ': ' . $data['url']);

        $dataStr = $this->jsonSerializer->serialize($data);

        $client = $this->util->getFlowClient('/webhooks', $storeId);
        $client->setMethod(Request::METHOD_POST);
        $client->setRawBody($dataStr);
        $response = $client->send();

        if ($response->isSuccess()) {
            $this->logger->info('Webhook event registered: ' . $response->getContent());
        } else {
            $this->logger->info('Webhook event registration failed: ' . $response->getContent());
        }
    }

    /**
     * Returns a list of webhooks registered with Flow.
     * @param $storeId - ID of store
     * @return string[]
     */
    private function getRegisteredWebhooks($storeId)
    {
        if (!$this->util->isFlowEnabled($storeId)) {
            return [];
        }

        $client = $this->util->getFlowClient('/webhooks', $storeId);
        $response = $client->send();

        if ($response->isSuccess()) {
            $content = $response->getBody();
            return $this->jsonSerializer->unserialize($content);
        } else {
            return [];
        }
    }

    /**
     * Returns the next unprocessed event.
     * @return WebhookEvent|null
     */
    private function getNextUnprocessedEvent()
    {
        $collection = $this->webhookEventCollectionFactory->create();
        $collection->addFieldToFilter('status', WebhookEvent::STATUS_NEW);
        $collection->addFieldToFilter('triggered_at', ['lteq' => (new \DateTime())->format('Y-m-d H:i:s')]);
        $collection->setOrder('updated_at', 'ASC');
        $collection->setOrder('id', 'ASC');
        $collection->setPageSize(1);
        if ($collection->getSize() == 0) {
            $return =  null;
        } else {
            /** @var WebhookEvent $return */
            $return = $collection->getFirstItem();
        }
        return $return;
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
            $message = substr((string) $message, 0, 200);
            $webHookEvent->setMessage($message);
        }
        $webHookEvent->setStatus(WebhookEvent::STATUS_DONE);
        $this->saveWebhookEvent($webHookEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function markWebhookEventAsError(WebhookEvent $webHookEvent, $errorMessage = null)
    {
        $errorMessage = substr((string) $errorMessage, 0, 200);
        $webHookEvent->setStatus(WebhookEvent::STATUS_ERROR);
        $webHookEvent->setMessage($errorMessage);
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
    public function requeue(WebhookEvent $webHookEvent, $message)
    {
        $interval = \DateInterval::createFromDateString(self::REQUEUE_MAX_AGE . ' seconds');
        $createdAt = \DateTime::createFromFormat('Y-m-d H:i:s', $webHookEvent->getCreatedAt());
        $triggeredAt = \DateTime::createFromFormat('Y-m-d H:i:s', $webHookEvent->getTriggeredAt());
        if ($createdAt->add($interval) < $triggeredAt) {
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
