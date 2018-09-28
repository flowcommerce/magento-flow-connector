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
