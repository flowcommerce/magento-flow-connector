<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku\Collection as SyncSkuCollection;
use FlowCommerce\FlowConnector\Model\SyncSku;
use FlowCommerce\FlowConnector\Model\SyncSkuManager as Subject;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test class for \FlowCommerce\FlowConnector\Model\SyncSkuManager
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class SyncSkuManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateProductsWithCategories
     */
    private $createProductsFixture;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Subject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var SyncSkuCollection
     */
    private $syncSkuCollection;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->syncSkuCollection = $this->objectManager->create(SyncSkuCollection::class);
        $this->subject = $this->objectManager->create(Subject::class);
    }

    /**
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 0
     */
    public function testDoesNotEnqueueWhenFlowModuleDisabled()
    {
        $this->createProductsFixture->execute();
        $this->subject->enqueueAllProducts();
        $this->syncSkuCollection->load();
        $this->assertEquals(0, $this->syncSkuCollection->count());
    }

    /**
     * @magentoConfigFixture current_store flowcommerce/flowconnector/enabled 1
     */
    public function testEnqueuesSuccessfullyWhenFlowModuleEnabled()
    {
        $this->createProductsFixture->execute();
        $products = $this->createProductsFixture->getProducts();
        $this->subject->enqueueAllProducts();
        $this->syncSkuCollection->load();

        $this->assertEquals($products->getTotalCount(), $this->syncSkuCollection->count());

        $productSkus = [];
        foreach ($products->getItems() as $product) {
            $productSkus[$product->getSku()] = $product->getSku();
        }

        /** @var SyncSku $syncSkuObject */
        foreach ($this->syncSkuCollection->getItems() as $syncSkuObject) {
            $syncSkuSku = $syncSkuObject->getSku();

            $this->assertEquals(SyncSku::STATUS_NEW, $syncSkuObject->getStatus());
            $this->assertEquals(1, $syncSkuObject->getStoreId());
            $this->assertArrayHasKey($syncSkuSku, $productSkus);

            if (array_key_exists($syncSkuSku, $productSkus)) {
                unset($productSkus[$syncSkuSku]);
            }
        }
        $this->assertCount(0, $productSkus);
    }
}
