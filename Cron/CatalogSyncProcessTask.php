<?php

namespace FlowCommerce\FlowConnector\Cron;

use \FlowCommerce\FlowConnector\Model\LockManager\CantAcquireLockException;
use \Psr\Log\LoggerInterface as Logger;
use \FlowCommerce\FlowConnector\Api\LockManagerInterface as LockManager;
use \FlowCommerce\FlowConnector\Model\Sync\CatalogSync;

/**
 * Cron Task wrapper class to run catalog sync process.
 * @package FlowCommerce\FlowConnector\Cron
 */
class CatalogSyncProcessTask
{
    /**
     * Lock manager - lock code
     */
    const LOCK_CODE = 'flowconnector_catalog_sync_lock';

    /**
     * Lock manager - lock ttl
     */
    const LOCK_TTL = 600;

    /**
     * @var LockManager
     */
    private $lockManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CatalogSync
     */
    private $catalogSync;

    /**
     * CatalogSyncProcessTask constructor.
     * @param LockManager $lockManager
     * @param Logger $logger
     * @param CatalogSync $catalogSync
     */
    public function __construct(
        Logger $logger,
        CatalogSync $catalogSync,
        LockManager $lockManager
    ) {
        $this->logger = $logger;
        $this->catalogSync = $catalogSync;
        $this->lockManager = $lockManager;
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
     * Defers queue processing to the the catalog sync model
     * @return void
     */
    public function execute()
    {
        try {
            $this->lockManager->setCustomLockTtl(self::LOCK_TTL);
            $this->acquireLock();
            $this->logger->info('Running CatalogSyncProcessTask execute.');
            $this->catalogSync->processAll();
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
