<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Cron;

use Magento\TestFramework\Helper\Bootstrap;
use FlowCommerce\FlowConnector\Cron\InventorySyncProcessTask as Subject;
use FlowCommerce\FlowConnector\Model\InventorySyncManager;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\LockManager as LockManagerFixture;
use FlowCommerce\FlowConnector\Api\LockManagerInterface as LockManager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;

/**
 * Class InventorySyncProcessTask
 * @package FlowCommerce\FlowConnector\Test\Integration\Cron
 *
 * @magentoAppIsolation enabled
 */
class InventorySyncProcessTask extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InventorySyncManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $inventorySyncManager;

    /**
     * @var LockManagerFixture
     */
    private $lockManagerFixture;

    /**
     * @var LockManager
     */
    private $lockManager;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Subject
     */
    private $subject;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->inventorySyncManager = $this->createPartialMock(InventorySyncManager::class, ['process']);
        $this->lockManager = $this->objectManager->create(LockManager::class);
        $this->lockManagerFixture = $this->objectManager->create(LockManagerFixture::class);
        $this->subject = $this->objectManager->create(Subject::class, [
            'inventorySyncManager' => $this->inventorySyncManager,
        ]);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testJobRunsProperlyWhenNoLockFlagExists()
    {
        $this->inventorySyncManager
            ->expects($this->once())
            ->method('process');

        $this->subject->execute();

        $this->assertFalse($this->lockManager->isLocked(Subject::LOCK_CODE));
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testJobRunsProperlyWhenLockFlagIsReleased()
    {
        $this->lockManagerFixture
            ->acquireLock(Subject::LOCK_CODE);

        $this->lockManagerFixture
            ->releaseLock(Subject::LOCK_CODE);

        $this->inventorySyncManager
            ->expects($this->once())
            ->method('process');

        $this->subject->execute();

        $this->assertFalse($this->lockManager->isLocked(Subject::LOCK_CODE));
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testJobDoesNotRunWhenFlagLocked()
    {
        $this->lockManagerFixture
            ->acquireLock(Subject::LOCK_CODE);

        $this->inventorySyncManager
            ->expects($this->never())
            ->method('process');

        $this->subject->execute();

        $this->assertTrue($this->lockManager->isLocked(Subject::LOCK_CODE));
    }
}
