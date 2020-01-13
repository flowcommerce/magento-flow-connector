<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\SessionManager as Subject;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CartBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
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
     * @var WebhookEvent
     */
    private $webhookEvent;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var OptionFactory
     */
    private $optionFactory;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->subject = $this->objectManager->create(Subject::class);
        $this->webhookEvent = $this->objectManager->create(WebhookEvent::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->customerSession = $this->objectManager->create(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->create(CheckoutSession::class);
        $this->productRepository = $this->objectManager->create(ProductRepository::class);
        $this->optionFactory = $this->objectManager->create(OptionFactory::class);
        $this->jsonSerializer = $this->objectManager->create(JsonSerializer::class);
    }

    /**
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateFlowOrderFormAddsOptions()
    {
        // Initialize data
        $this->createProductsFixture->execute();
        $cart = CartBuilder::forCurrentSession()
            ->withSimpleProduct('simple_4',3)
            ->build();
        $quote = $cart->getQuote();
        $item = $quote->getAllItems()[0];
        $itemId = $item->getId();
        $product = $this->productRepository->getById($item->getProductId());
        
        // Set Options
        $this->webhookEvent->setOptionValue($product, $item, 'Text', 'This one is just for dave');
        
        // Get Options
        $itemAfter = $quote->getItemById($itemId);

        // Set Flow Order Form
        $orderForm = $this->subject->createFlowOrderForm();
        $orderFormItem = $orderForm->items[0];
        
        // Assertions
        $this->assertEquals(
            $orderFormItem->number,
            $item->getSku()
        );
        $this->assertEquals(
            $orderFormItem->quantity,
            $item->getQty()
        );
        $this->assertEquals(
            '',
            $this->subject->getItemOptionsSerialized($itemAfter)
        );
    }
}
