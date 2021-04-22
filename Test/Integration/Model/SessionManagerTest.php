<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\SessionManager as Subject;
use FlowCommerce\FlowConnector\Model\WebhookEvent;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CartBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class SessionManagerTest
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
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
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * Sets up for tests
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->subject = $this->objectManager->create(Subject::class);
        $this->webhookEvent = $this->objectManager->create(WebhookEvent::class);
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->customerSession = $this->objectManager->create(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->create(CheckoutSession::class);
        $this->productRepository = $this->objectManager->create(ProductRepository::class);
        $this->jsonSerializer = $this->objectManager->create(JsonSerializer::class);
    }

    /**
     * @throws Exception
     * @magentoDbIsolation enabled
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
        
        $options = [
            [
                "product_id" => "4",
                "product" => (object)[],
                "code" => "info_buyRequest",
                "value" => "{\"qty\":3}",
                "item_id" => "1",
                "option_id" => "1"
            ],
            [ 
                "code" => "option_4",
                "value" => "This one is just for dave",
                "product_id" => "4",
                "item_id" => "1",
                "option_id" => "2" 
            ],
            [
                "code" => "option_ids",
                "value" => "4",
                "product_id" => "4",
                "item_id" => "1",
                "option_id" => "3"
            ]
        ];
        // Set Options
        $this->webhookEvent->setOptionValues($quote, $product, $item, $options);
        
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
        $requestedOptions = $this->jsonSerializer->unserialize($orderFormItem->attributes['options']);
        $requestedOptionValues = [];
        foreach ($requestedOptions as $requestedOption) {
            $requestedOptionValues[$requestedOption['code']] = $requestedOption['value'];
        }
        foreach ($itemAfter->getOptions() as $savedOption) {
            if (!in_array($savedOption->getCode(), ['info_buyRequest','option_ids'])) {
                $this->assertEquals(
                    $requestedOptionValues[$savedOption->getCode()],
                    $savedOption->getValue()
                );
            }
        }
    }
}
