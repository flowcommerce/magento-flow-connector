<?php

namespace FlowCommerce\FlowConnector\Api;

use FlowCommerce\FlowConnector\Model\LockManager\CantAcquireLockException;

interface LockManagerInterface
{
    /**
     * Acquires lock for given lock code
     * @param string $lockCode
     * @return void
     * @throws CantAcquireLockException
     */
    public function acquireLock($lockCode);

    /**
     * Checks if given lock code is locked
     * @param string $lockCode
     * @return bool
     */
    public function isLocked($lockCode);

    /**
     * Releases lock for given lock code
     * @param string $lockCode
     * @return void
     */
    public function releaseLock($lockCode);
}
