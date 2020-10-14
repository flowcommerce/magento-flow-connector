<?php

namespace FlowCommerce\FlowConnector\Cron;

use FlowCommerce\FlowConnector\Model\LockManager\CantAcquireLockException;
use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Api\LockManagerInterface as LockManager;
use \FlowCommerce\FlowConnector\Model\SyncOrderManager;

/**
 * Cron Task wrapper class to run order sync poll processing
 * @package FlowCommerce\FlowConnector\Cron
 */
class OrderSyncPollTask
{
    /**
     * Lock manager - lock code
     */
    const LOCK_CODE = 'flowcommerce_flowconnector_order_sync_lock';

    /**
     * @var LockManager
     */
    private $lockManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SyncOrderManager
     */
    private $syncOrderManager;

    /**
     * OrderSyncPollTask constructor.
     * @param LockManager $lockManager
     * @param Logger $logger
     * @param SyncOrderManager $syncOrderManager
     */
    public function __construct(
        LockManager $lockManager,
        Logger $logger,
        syncOrderManager $syncOrderManager
    ) {
        $this->lockManager = $lockManager;
        $this->logger = $logger;
        $this->syncOrderManager = $syncOrderManager;
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
            $this->logger->info('Running OrderSyncPollTask execute');
            $this->syncOrderManager->process();
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
