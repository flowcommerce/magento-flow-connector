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
        $this->customerSession = $this->objectManager->create(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->create(CheckoutSession::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateFlowOrderFormAddsOptions()
    {
        $this->createProductsFixture->execute();
        $cart = CartBuilder::forCurrentSession()
            ->withSimpleProduct('simple_4',4)
            ->build();
        $quote = $cart->getQuote();
        $customOptions = [
            [
                "option_id" => 1,
                "option_value" => "Hey there Flow!"
            ]
        ];
        $quote->getAllItems()[0]->setCustomOptions($customOptions);
        $orderForm = $this->subject->createFlowOrderForm();
        $this->assertEquals(
            $orderForm->items[0]->number,
            $quote->getAllItems()[0]->getSku()
        );
        $this->assertEquals(
            $orderForm->items[0]->quantity,
            $quote->getAllItems()[0]->getQty()
        );
        $orderFormBuyRequest = null;
        $testObj = new \Magento\Framework\DataObject(['1'=>'testing']);
        if (isset($orderForm->items[0]->attributes['info_buyRequest'])) {
            $orderFormBuyRequest = new \Magento\Framework\DataObject(json_decode($orderForm->items[0]->attributes['info_buyRequest'], true));
        }
        $this->assertEquals(
            $orderFormBuyRequest,
            $testObj
        );
        $this->assertEquals(
            $quote->getAllItems()[0]->getProductOptionByCode('info_buyRequest'),
            $testObj
        );
        $this->assertEquals(
            $orderFormBuyRequest,
            $quote->getAllItems()[0]->getProductOptionByCode('info_buyRequest')
        );
    }
}
