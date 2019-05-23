<?php

namespace FlowCommerce\FlowConnector\Model\LockManager;

use \FlowCommerce\FlowConnector\Api\LockManagerInterface;
use \FlowCommerce\FlowConnector\Model\LockManager\CantAcquireLockException;
use \Magento\Framework\FlagManager;

/**
 * Class Flag
 * @package FlowCommerce\FlowConnector\Model\LockManager
 */
class Flag implements LockManagerInterface
{
    /**
     * Flag Data Key - Locked
     */
    const DATA_KEY_LOCKED = 'locked';

    /**
     * Flag Data Key - Timestamp
     */
    const DATA_KEY_TIMESTAMP = 'timestamp';

    /**
     * Maximum time in seconds a lock can be acquired for
     */
    const LOCK_TTL = 120;

    /**
     * Custom TTL defined by lock
     * @var int|null
     */
    private $customTtl;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * Flag constructor.
     * @param FlagManager $flagManager
     */
    public function __construct(FlagManager $flagManager)
    {
        $this->flagManager = $flagManager;
    }

    /**
     * {@inheritdoc}
     */
    public function acquireLock($lockCode)
    {
        if ($this->isLocked($lockCode)) {
            if (!$this->isTtlExpired($lockCode)) {
                throw new CantAcquireLockException(
                    __('Could not acquire lock ' . $lockCode . ': it was already locked.')
                );
            }
        }

        $this->flagManager->saveFlag($lockCode, [
            self::DATA_KEY_LOCKED => true,
            self::DATA_KEY_TIMESTAMP => time()
        ]);
    }

    /**
     * Returns TTL for current lock
     * @return int
     */
    private function getLockTtl()
    {
        if ($this->customTtl === null) {
            $return = self::LOCK_TTL;
        } else {
            $return = $this->customTtl;
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked($lockCode)
    {
        $return = false;
        $flagData = (array) $this->flagManager->getFlagData($lockCode);
        if (array_key_exists(self::DATA_KEY_LOCKED, $flagData)) {
            return (bool) $flagData[self::DATA_KEY_LOCKED];
        }
        return $return;
    }

    /**
     * Checks whether ttl for current lock code has expired
     * @param string $lockCode
     * @return bool
     */
    private function isTtlExpired($lockCode)
    {
        $return = false;
        $flagData = $this->flagManager->getFlagData($lockCode);
        if (array_key_exists(self::DATA_KEY_TIMESTAMP, $flagData)) {
            $lockTimestamp = $flagData[self::DATA_KEY_TIMESTAMP];
            $currentTimestamp = time();
            $return = (bool) (($currentTimestamp - $lockTimestamp) > $this->getLockTtl());
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomLockTtl($seconds)
    {
        $seconds = (int) $seconds;

        if ($seconds > 0) {
            $this->customTtl = (int) $seconds;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function releaseLock($lockCode)
    {
        $this->flagManager->saveFlag($lockCode, [self::DATA_KEY_LOCKED => false]);
    }
}
