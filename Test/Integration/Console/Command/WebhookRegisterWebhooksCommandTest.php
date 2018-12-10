<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Console\Command;

use FlowCommerce\FlowConnector\Console\Command\WebhookRegisterWebhooksCommand as Subject;
use FlowCommerce\FlowConnector\Model\WebhookManager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test class for FlowCommerce\FlowConnector\Console\Command\WebhookRegisterWebhooksCommand
 * @package FlowCommerce\FlowConnector\Test\Integration\Console\Command
 */
class WebhookRegisterWebhooksCommandTest extends TestCase
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
     * @var WebhookManager
     */
    private $webhookManager;

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
        $this->webhookManager = $this->createPartialMock(
            WebhookManager::class,
            ['registerAllWebhooks', 'setLogger']
        );
        $this->subject = $this->objectManager->create(Subject::class, [
            'webhookManager' => $this->webhookManager
        ]);
        $this->tester = new CommandTester($this->subject);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testSuccessfulExecution()
    {
        $this->webhookManager
            ->expects($this->once())
            ->method('registerAllWebhooks')
            ->with(1)
            ->willReturn(true);
        $this->tester->execute([]);
    }
}
