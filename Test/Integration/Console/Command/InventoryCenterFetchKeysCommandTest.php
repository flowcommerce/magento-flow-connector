<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Console\Command;

use FlowCommerce\FlowConnector\Model\InventoryCenterManager;
use FlowCommerce\FlowConnector\Console\Command\InventoryCenterFetchKeysCommand as Subject;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test class for FlowCommerce\FlowConnector\Console\Command\InventoryCenterFetchKeysCommand
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @package FlowCommerce\FlowConnector\Test\Integration\Console\Command
 */
class InventoryCenterFetchKeysCommandTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Subject
     */
    private $subject;

    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->inventoryCenterManager = $this->createPartialMock(
            InventoryCenterManager::class,
            ['fetchInventoryCenterKeys']
        );
        $this->subject = $this->objectManager->create(Subject::class, [
            'inventoryCenterManager' => $this->inventoryCenterManager
        ]);
        $this->tester = new CommandTester($this->subject);
    }

    public function testSuccessfulExecution()
    {
        $this->inventoryCenterManager
            ->expects($this->once())
            ->method('fetchInventoryCenterKeys')
            ->with([1])
            ->willReturn(true);
        $this->tester->execute([]);
    }
}
