<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\SessionManager as Subject;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CartBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class SessionManagerTest
 * @package FlowCommerce\FlowConnector\Test\Integration\Model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SessionManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->subject = $this->objectManager->create(Subject::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->cartBuilder = $this->objectManager->create(CartBuilder::class);
        $this->customerSession = $this->objectManager->create(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->create(CheckoutSession::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateFlowOrderFormAddsAvailableInfoBuyRequest()
    {
        $this->createProductsFixture->execute();
        $quote = $this->cartBuilder->build()->withSimpleProduct('simple_4', 4);
        $orderForm = $this->subject->createFlowOrderForm();
        $this->assertEquals(
            $orderForm->items[0]['number'],
            $quote->getFirstItem()->getSku()
        );
        $this->assertEquals(
            $orderForm->items[0]['quantity'],
            $quote->getFirstItem()->getQty()
        );
        $this->assertEquals(
            json_decode($orderForm->items[0]['attributes'][$this->subject::INFO_BUYREQUEST_LABEL]),
            $quote->getFirstItem()->getProductOptionByCode($this->subject::INFO_BUYREQUEST_LABEL)
        );
    }
}
