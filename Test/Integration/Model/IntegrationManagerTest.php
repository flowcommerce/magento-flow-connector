<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\InventoryCenterManager;
use FlowCommerce\FlowConnector\Model\SyncSkuPriceAttributesManager;
use FlowCommerce\FlowConnector\Model\WebhookManager;
use FlowCommerce\FlowConnector\Model\IntegrationManager as Subject;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class InventoryCenterManagerTest
 * @package FlowCommerce\FlowConnector\Test\Integration\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IntegrationManagerTest extends TestCase
{
    const STORE_ID = 1;

    /**
     * @var InventoryCenterManager
     */
    private $inventoryCenterManager;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Subject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var SyncSkuPriceAttributesManager
     */
    private $syncSkuPriceAttributesManager;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var WebhookManager
     */
    private $webhookManager;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->create(StoreManager::class);
        $this->inventoryCenterManager = $this->createPartialMock(
            InventoryCenterManager::class,
            ['fetchInventoryCenterKeys']
        );
        $this->syncSkuPriceAttributesManager = $this->createPartialMock(
            SyncSkuPriceAttributesManager::class,
            ['createPriceAttributesInFlow']
        );
        $this->webhookManager = $this->createPartialMock(
            WebhookManager::class,
            ['registerAllWebhooks']
        );
        $this->subject = $this->objectManager->create(Subject::class, [
            'inventoryCenterManager' => $this->inventoryCenterManager,
            'syncSkuPriceAttributesManager' => $this->syncSkuPriceAttributesManager,
            'webhookManager' => $this->webhookManager
        ]);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     * @magentoConfigFixture current_store flowcommerce/flowconnector/organization_id organization-id
     * @magentoConfigFixture current_store flowcommerce/flowconnector/api_token api-token
     * @throws NoSuchEntityException
     */
    public function testSuccessfullyExecutesWhenModuleEnabled()
    {
        $this->inventoryCenterManager
            ->expects($this->once())
            ->method('fetchInventoryCenterKeys')
            ->with([self::STORE_ID])
            ->willReturn(true);
        $this->syncSkuPriceAttributesManager
            ->expects($this->once())
            ->method('createPriceAttributesInFlow')
            ->with(self::STORE_ID)
            ->willReturn(true);
        $this->webhookManager
            ->expects($this->once())
            ->method('registerAllWebhooks')
            ->with(self::STORE_ID)
            ->willReturn(true);

        foreach ($this->storeManager->getStores() as $store) {
            $this->subject->initializeIntegrationForStoreView($store->getId());
        }
    }
}
