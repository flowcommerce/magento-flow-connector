<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Console\Command;

use FlowCommerce\FlowConnector\Api\SyncSkuPriceAttributesManagementInterface;
use FlowCommerce\FlowConnector\Console\Command\CatalogSyncAttributesSaveCommand as Subject;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Test class for FlowCommerce\FlowConnector\Console\Command\CatalogSyncAttributesSaveCommand
 * @magentoAppIsolation enabled
 * @package FlowCommerce\FlowConnector\Test\Integration\Console\Command
 */
class CatalogSyncAttributesSaveCommandTest extends TestCase
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
     * @var SyncSkuPriceAttributesManagementInterface
     */
    private $syncSkuPriceAttributesManager;

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
        $this->syncSkuPriceAttributesManager = $this->createPartialMock(
            SyncSkuPriceAttributesManagementInterface::class,
            ['createPriceAttributesInFlow']
        );
        $this->subject = $this->objectManager->create(Subject::class, [
            'syncSkuPriceAttributesManager' => $this->syncSkuPriceAttributesManager
        ]);
        $this->tester = new CommandTester($this->subject);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testSuccessfulExecution()
    {
        $this->syncSkuPriceAttributesManager
            ->expects($this->once())
            ->method('createPriceAttributesInFlow')
            ->willReturn(true);
        $this->tester->execute([]);
    }
}
