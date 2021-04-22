<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Console\Command;

use FlowCommerce\FlowConnector\Api\IntegrationManagementInterface;
use FlowCommerce\FlowConnector\Console\Command\IntegrationInitializeCommand as Subject;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test class for FlowCommerce\FlowConnector\Console\Command\IntegrationInitializeCommand
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @package FlowCommerce\FlowConnector\Test\Integration\Console\Command
 */
class IntegrationInitializeCommandTest extends TestCase
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
     * @var IntegrationManagementInterface
     */
    private $integrationManager;

    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->integrationManager = $this->createPartialMock(
            IntegrationManagementInterface::class,
            ['initializeIntegrationForStoreView']
        );
        $this->subject = $this->objectManager->create(Subject::class, [
            'integrationManager' => $this->integrationManager
        ]);
        $this->tester = new CommandTester($this->subject);
    }

    public function testSuccessfulExecution()
    {
        $this->integrationManager
            ->expects($this->once())
            ->method('initializeIntegrationForStoreView')
            ->with(1)
            ->willReturn(true);
        $this->tester->execute([]);
    }
}
