<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Cron;

use FlowCommerce\FlowConnector\Api\LockManagerInterface as LockManager;
use FlowCommerce\FlowConnector\Cron\WebhookEventProcessTask as Subject;
use FlowCommerce\FlowConnector\Model\LockManager\Flag;
use FlowCommerce\FlowConnector\Model\WebhookEventManager;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\LockManager as LockManagerFixture;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for FlowCommerce\FlowConnector\Cron\CatalogSyncProcessTask
 * @magentoAppIsolation enabled
 * @package FlowCommerce\FlowConnector\Test\Integration\Cron
 */
class WebhookEventProcessTaskTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebhookEventManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $webhookEventManager;

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
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->webhookEventManager = $this->createPartialMock(WebhookEventManager::class, ['processAll']);
        $this->lockManager = $this->objectManager->create(LockManager::class);
        $this->lockManagerFixture = $this->objectManager->create(LockManagerFixture::class);
        $this->subject = $this->objectManager->create(Subject::class, [
            'webhookEventManager' => $this->webhookEventManager,
        ]);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testJobRunsProperlyWhenNoLockFlagExists()
    {
        $this->webhookEventManager
            ->expects($this->once())
            ->method('processAll');

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

        $this->webhookEventManager
            ->expects($this->once())
            ->method('processAll');

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

        $this->webhookEventManager
            ->expects($this->never())
            ->method('processAll');

        $this->subject->execute();

        $this->assertTrue($this->lockManager->isLocked(Subject::LOCK_CODE));
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testJobRunsWhenFlagLockedButTtlExpired()
    {
        $this->lockManagerFixture
            ->acquireLock(Subject::LOCK_CODE, (time() - (Flag::LOCK_TTL + 10)));

        $this->webhookEventManager
            ->expects($this->once())
            ->method('processAll');

        $this->subject->execute();

        $this->assertFalse($this->lockManager->isLocked(Subject::LOCK_CODE));
    }
}
