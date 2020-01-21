<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Console\Command;

use FlowCommerce\FlowConnector\Console\Command\WebhookRegisterWebhooksCommand as Subject;
use FlowCommerce\FlowConnector\Model\WebhookManager;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test class for @see \FlowCommerce\FlowConnector\Console\Command\WebhookRegisterWebhooksCommand
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
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
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/default_center_key center-1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/api_token 0123456789
     * @magentoConfigFixture current_store flowcommerce/flowconnector/organization_id 0123456789
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
