<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Fixtures;

use \FlowCommerce\FlowConnector\Model\LockManager\Flag as FlowLockManager;
use \Magento\Framework\FlagManager;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;
use \Magento\TestFramework\Helper\Bootstrap;

/**
 * Class LockManager
 * @package FlowCommerce\FlowConnector\Test\Integration\Fixtures
 */
class LockManager
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var FlowLockManager
     */
    private $lockManager;

    /**
     * @var ObjectManager
     */
    private $objectManager;


    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->flagManager = $this->objectManager->create(FlagManager::class);
        $this->lockManager = $this->objectManager->create(FlowLockManager::class);
    }

    /**
     * Acquires a lock with given code and timestamp
     * @param string $lockCode
     * @param null $timestamp
     */
    public function acquireLock($lockCode, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $this->flagManager->saveFlag($lockCode, [
            FlowLockManager::DATA_KEY_LOCKED => true,
            FlowLockManager::DATA_KEY_TIMESTAMP => $timestamp
        ]);
    }

    /**
     * Acquires a lock with given code and timestamp
     * @param string $lockCode
     * @param null $timestamp
     */
    public function releaseLock($lockCode, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $this->flagManager->saveFlag($lockCode, [
            FlowLockManager::DATA_KEY_LOCKED => false,
            FlowLockManager::DATA_KEY_TIMESTAMP => $timestamp
        ]);
    }
}
