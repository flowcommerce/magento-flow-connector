<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Model;

use FlowCommerce\FlowConnector\Model\SessionManager as Subject;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CreateProductsWithCategories;
use FlowCommerce\FlowConnector\Test\Integration\Fixtures\CartBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product\OptionFactory;
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
        $this->createProductsFixture = $this->objectManager->create(CreateProductsWithCategories::class);
        $this->customerSession = $this->objectManager->create(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->create(CheckoutSession::class);
        $this->productRepository = $this->objectManager->create(ProductRepository::class);
        $this->optionFactory = $this->objectManager->create(OptionFactory::class);
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
        $product = $this->productRepository->getById($item->getProductId());
        
        // Set Options
        $this->setOptionValue($product, $item, 'Text', 'testing');
        
        // Get Options
        $itemOptions = $item->getOptions();
        $optionsArray = [];
        foreach ($itemOptions as $option) {
            if ($option->getCode() && $option->getValue()) {
                $optionsArray[] = [
                    $option->getCode() => $option->getValue()
                ];
            }
        }

        // Set Flow Order Form
        $orderForm = $this->subject->createFlowOrderForm();
        $orderFormItem = $orderForm->items[0];
        $orderFormAttributesOptions = null;
        if (isset($orderFormItem->attributes['options'])) {
            $orderFormAttributesOptions = json_decode($orderFormItem->attributes['options'], true);
        }
        
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
            $orderFormAttributesOptions,
            $optionsArray
        );
    }

    private function setOptionValue($product = null, $item = null, $optionTitle = null, $value = null)
    {
        $option = $this->getOption($product, $optionTitle);
        if (is_null($option)) {
            throw new \Exception("Option not found with title \"{$optionTitle}\"");
        }

        if (!$option->getId()) {
            throw new \Exception("Option not found with title \"{$optionTitle}\"");
        }

        if (is_null($product)) {
            throw new \Exception("Product not found");
        }

        if (!$product->getId()) {
            throw new \Exception("Product not found");
        }

        if (is_null($value)) {
            throw new \Exception("Value \"$valueTitle\" could not be set for option \"{$option->getTitle()}\" for product {$product->getId()}");
        }

        $item->addOption(
            $this->optionFactory->create()
                 ->setCode('option_'.$option->getId())
                 ->setProduct($product)
                 ->setValue($value)
        );

        $item->addOption(
            $this->optionFactory->create()
                 ->setCode('option_ids')
                 ->setProduct($product)
                 ->setValue($option->getId())
        );

        $item->saveItemOptions();

         return $item;
    }

    private function getOption($product, $title)
    {
        foreach ($product->getOptions() as $option) {
            if ($option->getTitle() == $title) {
                return $option;
            }
        }
    }
}
