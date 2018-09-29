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
     * Number of jobs to be processed at every run
     */
    const NUMBER_OF_JOBS_TO_PROCESS = 1000;

    /**
     * Number of seconds to wait after existing queue is processed
     */
    const KEEP_ALIVE_AFTER_QUEUE_IS_PROCESSED = 10;

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
     * Returns the number of seconds to wait after a queue is processed.
     * The WebhookEvent model will attempt to find new jobs be processed.
     * @return int
     */
    private function getKeepAliveAfterQueueIsProcessed()
    {
        return self::KEEP_ALIVE_AFTER_QUEUE_IS_PROCESSED;
    }

    /**
     * Returns the number of jobs to be processed at every cron job run
     * @return int
     */
    private function getNumberOfJobsToProcess()
    {
        return self::NUMBER_OF_JOBS_TO_PROCESS;
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
            $this->webhookEventManager->process($this->getNumberOfJobsToProcess(), $this->getKeepAliveAfterQueueIsProcessed());
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
