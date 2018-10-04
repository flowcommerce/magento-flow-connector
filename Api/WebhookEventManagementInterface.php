<?php

namespace FlowCommerce\FlowConnector\Api;

use FlowCommerce\FlowConnector\Model\WebhookEvent;
use Magento\Framework\Exception\LocalizedException;

/**
 * Interface Webhook Management Interface
 * @package FlowCommerce\FlowConnector\Api
 */
interface WebhookEventManagementInterface
{
    /**
     * Deletes old processed items.
     * @return void
     * @throws LocalizedException
     */
    public function deleteOldProcessedEvents();

    /**
     * Given a Flow Order ID and a webhook type, marks all events related to that order and type as done
     * @param string $flowOrderId
     * @param string $type
     * @return void
     */
    public function markPendingWebhookEventsAsDoneForOrderAndType($flowOrderId, $type);

    /**
     * Marks Web Hook as done
     * @param WebhookEvent $webHookEvent
     * @param string|null $message
     * @return WebhookEvent
     */
    public function markWebhookEventAsDone(WebhookEvent $webHookEvent, $message = null);

    /**
     * Marks Web Hook as error
     * @param WebhookEvent $webHookEvent
     * @param string|null $errorMessage
     * @return WebhookEvent
     */
    public function markWebhookEventAsError(WebhookEvent $webHookEvent, $errorMessage = null);

    /**
     * Marks Web Hook as processing
     * @param WebhookEvent $webHookEvent
     * @return WebhookEvent
     */
    public function markWebhookEventAsProcessing(WebhookEvent $webHookEvent);

    /**
     * Adds a Webhook Event to the queue again
     * @param WebhookEvent $webHookEvent
     * @param string $message
     * @return void
     */
    public function requeue(WebhookEvent $webHookEvent, $message);

    /**
     * Deletes items with errors where there is a new record that is done.
     * @return void
     * @throws LocalizedException
     */
    public function resetOldErrorEvents();
}
