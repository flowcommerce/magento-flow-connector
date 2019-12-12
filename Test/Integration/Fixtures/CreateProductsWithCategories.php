<?php

namespace FlowCommerce\FlowConnector\Test\Integration\Fixtures;

use \Magento\Catalog\Api\CategoryRepositoryInterface as CategoryRepository;
use \Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use \Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use \Magento\Catalog\Model\Category;
use \Magento\Catalog\Model\Product;
use \Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use \Magento\Catalog\Model\Product\Type as ProductType;
use \Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use \Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeResourceModel;
use \Magento\Catalog\Setup\CategorySetup as Installer;
use \Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use \Magento\Eav\Api\AttributeRepositoryInterface as AttributeRepository;
use \Magento\Eav\Model\Config as EavConfig;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;

/**
 * Class CreateProductsWithCategories
 * @package FlowCommerce\FlowConnector\Test\Integration\Fixtures
 */
class CreateProductsWithCategories
{
    /**
     * SKU Prefix for simple products
     */
    const SKU_PREFIX = 'simple_';

    /**
     * @var AttributeRepository
     */
    private $attributeRepository;

    /**
     * @var AttributeResourceModel
     */
    private $attributeResourceModel;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var ConfigurableOptionsFactory
     */
    private $configurableOptionsFactory;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var Installer
     */
    private $installer;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->attributeRepository = $this->objectManager->create(AttributeRepository::class);
        $this->attributeResourceModel = $this->objectManager->create(AttributeResourceModel::class);
        $this->categoryRepository = $this->objectManager->create(CategoryRepository::class);
        $this->configurableOptionsFactory = $this->objectManager->create(ConfigurableOptionsFactory::class);
        $this->eavConfig = $this->objectManager->create(EavConfig::class);
        $this->installer = $this->objectManager->create(Installer::class);
        $this->productRepository = $this->objectManager->create(ProductRepository::class);
        $this->searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);
    }

    /**
     * Creates products to be enqueued by the enqueue all command
     */
    public function execute()
    {
        /**
         * Create Categories
         */

        /** @var Category $category1 */
        $category1 = $this->objectManager->create(Category::class);
        $category1->isObjectNew(true);
        $category1
            ->setId(501)
            ->setCreatedAt('2014-06-23 09:50:07')
            ->setName('Category 1')
            ->setParentId(2)
            ->setLevel(2)
            ->setAvailableSortBy('name')
            ->setIsActive(true)
            ->setPosition(1)
            ->setDefaultSortBy('name')
            ->setPath('1/2/501')
            ->save($category1);

        /** @var Category $category2 */
        $category2 = $this->objectManager->create(Category::class);
        $category2->isObjectNew(true);
        $category2
            ->setId(502)
            ->setCreatedAt('2014-06-23 09:50:07')
            ->setName('Category 2')
            ->setParentId($category1->getId())
            ->setLevel(3)
            ->setAvailableSortBy('name')
            ->setIsActive(true)
            ->setPosition(1)
            ->setDefaultSortBy('name')
            ->setPath('1/2/501/502')
            ->save($category2);

        /** @var Category $category3 */
        $category3 = $this->objectManager->create(Category::class);
        $category3->isObjectNew(true);
        $category3
            ->setId(503)
            ->setCreatedAt('2014-06-23 09:50:07')
            ->setName('Category 3')
            ->setParentId(2)
            ->setLevel(2)
            ->setAvailableSortBy('name')
            ->setIsActive(true)
            ->setPosition(1)
            ->setDefaultSortBy('name')
            ->setPath('1/2/503')
            ->save($category3);

        /** @var Category $category4 */
        $category4 = $this->objectManager->create(Category::class);
        $category4->isObjectNew(true);
        $category4
            ->setId(504)
            ->setCreatedAt('2014-06-23 09:50:07')
            ->setName('Category 4')
            ->setParentId($category3->getId())
            ->setLevel(3)
            ->setAvailableSortBy('name')
            ->setIsActive(true)
            ->setPosition(1)
            ->setPath('1/2/503/504')
            ->setDefaultSortBy('name')
            ->save($category4);

        /** @var Category $category5 */
        $category5 = $this->objectManager->create(Category::class);
        $category5->isObjectNew(true);
        $category5
            ->setId(505)
            ->setCreatedAt('2014-06-23 09:50:07')
            ->setName('Category 5')
            ->setParentId(2)
            ->setLevel(2)
            ->setAvailableSortBy('name')
            ->setIsActive(true)
            ->setPosition(1)
            ->setPath('1/2/505')
            ->setDefaultSortBy('name')
            ->save($category5);

        /**
         * Create Configurable Attribute
         */
        $this->attributeResourceModel = $this->eavConfig->getAttribute('catalog_product', 'test_configurable');
        $this->eavConfig->clear();
        if (!$this->attributeResourceModel->getId()) {
            $this->attributeResourceModel
                ->setData(
                    [
                        'attribute_code' => 'test_configurable',
                        'entity_type_id' => $this->installer->getEntityTypeId('catalog_product'),
                        'is_global' => 1,
                        'is_user_defined' => 1,
                        'frontend_input' => 'select',
                        'is_unique' => 0,
                        'is_required' => 0,
                        'is_searchable' => 0,
                        'is_visible_in_advanced_search' => 0,
                        'is_comparable' => 0,
                        'is_filterable' => 0,
                        'is_filterable_in_search' => 0,
                        'is_used_for_promo_rules' => 0,
                        'is_html_allowed_on_front' => 1,
                        'is_visible_on_front' => 0,
                        'used_in_product_listing' => 0,
                        'used_for_sort_by' => 0,
                        'frontend_label' => ['Test Configurable'],
                        'backend_type' => 'int',
                        'option' => [
                            'value' =>
                                [
                                    'option_0' => ['Option 1'],
                                    'option_1' => ['Option 2'],
                                    'option_2' => ['Option 3']
                                ],
                            'order' => [
                                'option_0' => 1,
                                'option_1' => 2,
                                'option_2' => 3
                            ],
                        ],
                    ]
                );
            $this->attributeRepository->save($this->attributeResourceModel);
            $this
                ->installer
                ->addAttributeToGroup('catalog_product', 'Default', 'General', $this->attributeResourceModel->getId());
        }
        $this->eavConfig->clear();

        $attributeValues = [];
        $associatedProductIds = [];

        $options = $this->attributeResourceModel->getOptions();

        $i = 0;
        foreach ($options as $option) {
            if ($option->getValue() == '') {
                continue;
            }

            $i++;
            /** @var $product Product */
            $product = $this->objectManager->create(Product::class);
            $product->setTypeId(ProductType::TYPE_SIMPLE)
                ->setAttributeSetId(4)
                ->setStoreId(1)
                ->setWebsiteIds([1])
                ->setName('Simple Product ' . $i)
                ->setSku(self::SKU_PREFIX . $i)
                ->setPrice(10)
                ->setWeight(18)
                ->setStockData(['use_config_manage_stock' => 1, 'qty' => 999999, 'is_qty_decimal' => 0, 'is_in_stock' => 1])
                ->setVisibility(ProductVisibility::VISIBILITY_BOTH)
                ->setStatus(ProductStatus::STATUS_ENABLED)
                ->setCategoryIds([$category2->getId(), $category4->getId(), $category5->getId()])
                ->setTestConfigurable($option->getValue())
                ->setHasOptions(1)
                ->setCanSaveCustomOptions(true);
            $this->productRepository->cleanCache();
            $product = $this->productRepository->save($product);

            $fieldOption = $this->objectManager->create('\Magento\Catalog\Model\Product\Option')
                                         ->setProductId($product->getId())
                                         ->setStoreId($product->getStoreId())
                                         ->addData([
                                             "sort_order"    => 0,
                                             "title"         => "Field Option",
                                             "price_type"    => "fixed",
                                             "price"         => "",
                                             "type"          => "field",
                                             "is_require"    => 0
                                         ]);
            $fieldOption->save();
            $product->addOption($fieldOption);

            $product = $this->productRepository->save($product);

            $attributeValues[] = [
                'label' => 'test',
                'attribute_id' => $this->attributeResourceModel->getId(),
                'value_index' => $option->getValue(),
            ];
            $associatedProductIds[] = $product->getId();
        }

        /**
         * Creating configurable product
         */
        $configurableProduct = $this->objectManager->create(Product::class);
        $attributeSetId = $this->installer->getAttributeSetId('catalog_product', 'Default');

        /** @var Factory $optionsFactory */
        $configurableAttributesData = [
            [
                'attribute_id' => $this->attributeResourceModel->getId(),
                'code' => $this->attributeResourceModel->getAttributeCode(),
                'label' => $this->attributeResourceModel->getStoreLabel(),
                'position' => '0',
                'values' => $attributeValues,
            ],
        ];
        $configurableOptions = $this->configurableOptionsFactory->create($configurableAttributesData);
        $extensionConfigurableAttributes = $configurableProduct->getExtensionAttributes();
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
        $configurableProduct->setExtensionAttributes($extensionConfigurableAttributes);

        $configurableProduct
            ->setTypeId(Configurable::TYPE_CODE)
            ->setAttributeSetId($attributeSetId)
            ->setWebsiteIds([1])
            ->setName('Configurable Product')
            ->setSku('configurable')
            ->setVisibility(ProductVisibility::VISIBILITY_BOTH)
            ->setStatus(ProductStatus::STATUS_ENABLED)
            ->setCategoryIds([$category2->getId(), $category4->getId(), $category5->getId()])
            ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
        $this->productRepository->cleanCache();
        $this->productRepository->save($configurableProduct);
    }

    /**
     * @return ProductSearchResultsInterface
     */
    public function getProducts()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->productRepository->getList($searchCriteria);
    }
}
