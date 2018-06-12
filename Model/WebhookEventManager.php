<?php

namespace Flow\FlowConnector\Model;

use Magento\Framework\UrlInterface;
use Zend\Http\{
    Client,
    Request
};

/**
 * Helper class to manage WebhookEvent processing.
 */
class WebhookEventManager {

    private $logger;
    private $jsonHelper;
    private $util;
    private $webhookEventFactory;
    private $storeManager;
    private $objectManager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Flow\FlowConnector\Model\Util $util,
        \Flow\FlowConnector\Model\WebhookEventFactory $webhookEventFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->util = $util;
        $this->webhookEventFactory = $webhookEventFactory;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
    }

    /**
    * Set the logger (used by console command).
    */
    public function setLogger($logger) {
        $this->logger = $logger;
    }

    /**
    * Queue webhook event for processing.
    */
    public function queue($type, $payload) {
        $this->logger->info('Queue webhook event type: ' . $type);

        $payloadData = $this->jsonHelper->jsonDecode($payload);
        $timestamp = $payloadData['timestamp'];

        $model = $this->webhookEventFactory->create();
        $model->setType($type);
        $model->setPayload($payload);
        $model->setStatus(WebhookEvent::STATUS_NEW);
        $model->setTriggeredAt($timestamp);
        $model->save();
        return $model;
    }

    /**
    * Process the webhook event queue.
    * @param numToProcess Number of records to process.
    * @param keepAlive Number of seconds to keep alive after/between processing.
    */
    public function process($numToProcess = 1000, $keepAlive = 60) {
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
    */
    public function registerWebhooks() {
        $this->deleteAllWebhooks();
        $this->registerWebhook('allocationdeletedv2', 'allocation_deleted_v2');
        $this->registerWebhook('allocationupsertedv2', 'allocation_upserted_v2');
        $this->registerWebhook('authorizationdeletedv2', 'authorization_deleted_v2');
        $this->registerWebhook('authorizationupserted', 'authorization_upserted');
        $this->registerWebhook('captureupsertedv2', 'capture_upserted_v2');
        $this->registerWebhook('cardauthorizationupsertedv2', 'card_authorization_upserted_v2');
        $this->registerWebhook('localitemdeleted', 'local_item_deleted');
        $this->registerWebhook('localitemupserted', 'local_item_upserted');
        $this->registerWebhook('onlineauthorizationupsertedv2', 'online_authorization_upserted_v2');
        $this->registerWebhook('orderdeleted', 'order_deleted');
        $this->registerWebhook('orderupserted', 'order_upserted');
        $this->registerWebhook('refundcaptureupsertedv2', 'refund_capture_upserted_v2');
        $this->registerWebhook('refundupsertedv2', 'refund_upserted_v2');
        $this->registerWebhook('fraudstatuschanged', 'fraud_status_changed');
        $this->registerWebhook('trackinglabeleventupserted', 'tracking_label_event_upserted');
        return true;
    }

    /**
    * Delete all Flow connector webhooks.
    */
    private function deleteAllWebhooks() {
        $webhooks = $this->getRegisteredWebhooks();
        foreach($webhooks as $webhook) {
            if (strpos($webhook['url'], '/flowconnector/webhooks/')) {
                $this->logger->info('Deleting webhook: ' . $webhook['url']);
                $client = $this->util->getFlowClient('/webhooks/' . $webhook['id']);
                $client->setMethod(Request::METHOD_DELETE);
                $client->send();
            }
        }
    }

    /**
    * Registers a webhook with Flow.
    */
    private function registerWebhook($endpointStub, $event) {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        $data = [
            "url" => $baseUrl . "flowconnector/webhooks/{$endpointStub}",
            "events" => $event
        ];

        $this->logger->info('Registering webhook event '. $event . ': ' . $data['url']);

        $dataStr = $this->jsonHelper->jsonEncode($data);

        $client = $this->util->getFlowClient('/webhooks');
        $client->setMethod(Request::METHOD_POST);
        $client->setRawBody($dataStr);
        $response = $client->send();

        if ($response->isSuccess()) {
            $this->logger->info('Webhook event registered: ' . $response->getContent());
        } else {
            $this->logger->info('Webhook event registration failed: ' , $response->getContent());
        }
    }

    /**
    * Returns a list of webhooks registered with Flow.
    */
    private function getRegisteredWebhooks() {
        if ($this->util->getFlowOrganizationId() == null) {
            return [];
        }

        $client = $this->util->getFlowClient('/webhooks');
        $response = $client->send();

        if ($response->isSuccess()) {
            $content = $response->getBody();
            return $this->jsonHelper->jsonDecode($content);
        } else {
            return [];
        }
    }

    /**
    * Returns the next unprocessed event.
    */
    private function getNextUnprocessedEvent() {
        $collection = $this->webhookEventFactory->create()->getCollection();
        $collection->addFieldToFilter('status', WebhookEvent::STATUS_NEW);
        $collection->addFieldToFilter('triggered_at', ['lteq' => (new \DateTime())->format('Y-m-d H:i:s')]);
        $collection->setOrder('updated_at', 'ASC');
        $collection->setPageSize(1);
        if ($collection->getSize() == 0) {
            return null;
        } else {
            return $collection->getFirstItem();
        }
    }

    /**
    * Deletes old processed webhook events.
    */
    private function deleteOldProcessedEvents() {
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = 'delete from flow_connector_webhook_events where status=\'' . WebhookEvent::STATUS_DONE . '\' and updated_at < date_sub(now(), interval 96 hour)';
        $connection->query($sql);
    }

    /**
    * Reset any webhook events that have been stuck processing for too long.
    */
    private function resetOldErrorEvents() {
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = 'update flow_connector_webhook_events set status=\'' . WebhookEvent::STATUS_NEW . '\' where status=\'' . WebhookEvent::STATUS_PROCESSING . '\' and updated_at < date_sub(now(), interval 4 hour)';
        $connection->query($sql);
    }
}
