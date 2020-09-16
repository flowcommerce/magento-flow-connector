<?php

namespace FlowCommerce\FlowConnector\Cron;

use FlowCommerce\FlowConnector\Model\LockManager\CantAcquireLockException;
use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Api\LockManagerInterface as LockManager;
use \FlowCommerce\FlowConnector\Model\WebhookEventManager;

/**
 * Cron Task wrapper class to run webhook event processing.
 * @package FlowCommerce\FlowConnector\Cron
 */
class WebhookEventProcessTask
{
    /**
     * Lock manager - lock code
     */
    const LOCK_CODE = 'flowcommerce_flowconnector_webhook_lock';

    /**
     * @var LockManager
     */
    private $lockManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var WebhookEventManager
     */
    private $webhookEventManager;

    /**
     * WebhookEventProcessTask constructor.
     * @param LockManager $lockManager
     * @param Logger $logger
     * @param WebhookEventManager $webhookEventManager
     */
    public function __construct(
        LockManager $lockManager,
        Logger $logger,
        WebhookEventManager $webhookEventManager
    ) {
        $this->lockManager = $lockManager;
        $this->logger = $logger;
        $this->webhookEventManager = $webhookEventManager;
    }

    /**
     * Acquires lock for this job
     * @return void
     * @throws CantAcquireLockException
     */
    private function acquireLock()
    {
        $this->lockManager->acquireLock(self::LOCK_CODE);
    }

    /**
     * Defers webhook events processing to the webhook event manager model
     * @return void
     */
    public function execute()
    {
        try {
            $this->acquireLock();
            $this->logger->info('Running WebhookEventProcessTask execute');
            $this->webhookEventManager->processAll();
            $this->releaseLock();
        } catch (CantAcquireLockException $e) {
            $this->logger->info($e->getMessage());
        }
    }

    /**
     * Releases lock for this job
     * @return void
     */
    private function releaseLock()
    {
        $this->lockManager->releaseLock(self::LOCK_CODE);
    }
}
