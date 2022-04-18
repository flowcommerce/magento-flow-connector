<?php

namespace FlowCommerce\FlowConnector\Model\Api\Item\Save;

use Exception;
use \Magento\Framework\UrlInterface;
use GuzzleHttp\Client as GuzzleClient;
use \Psr\Log\LoggerInterface as Logger;
use \Magento\Catalog\Model\ProductFactory;
use \Magento\Framework\App\Area as AppArea;
use \FlowCommerce\FlowConnector\Model\SyncSku;
use \Magento\Catalog\Api\Data\ProductInterface;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\View\Element\BlockFactory;
use FlowCommerce\FlowConnector\Model\Configuration;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Store\Model\ScopeInterface as StoreScope;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Store\Model\App\Emulation as AppEmulation;
use \Magento\Framework\Locale\Resolver as LocaleResolver;
use \Magento\Store\Model\StoreManagerInterface as StoreManager;
use \Magento\Catalog\Block\Product\ListProduct as ListProductBlock;
use \Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use \Magento\Framework\App\ProductMetadataInterface as ProductMetaData;
use \Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use \Magento\ConfigurableProduct\Api\LinkManagementInterface as LinkManagement;
use \FlowCommerce\FlowConnector\Api\SyncSkuManagementInterface as SyncSkuManager;
use \Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableTypeResourceModel;
use Magento\Directory\Model\CountryFactory;

/**
 * Class ProductDataMapper
 * @package FlowCommerce\FlowConnector\Model\Api\Item
 */
class ProductDataMapper
{
    /**
     * Flow unit measurements mapped to abbreviations.
     * https://docs.flow.io/type/unit-of-measurement
     */
    const FLOW_UNIT_MEASUREMENTS = [
        'millimeter' => ['mm'],
        'centimeter' => ['cm'],
        'inch' => ['in'],
        'foot' => ['ft'],
        'cubic_inch' => ['cu in'],
        'cubic_meter' => ['cu m'],
        'gram' => ['g'],
        'kilogram' => ['kg'],
        'meter' => ['m'],
        'ounce' => ['oz'],
        'pound' => ['lb', 'lbs'],
    ];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var LinkManagement
     */
    private $linkManagement;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var AppEmulation
     */
    private $appEmulation;

    /**
     * @var LocaleResolver
     */
    private $localeResolver;

    /**
     * @var ScopeConfig
     */
    private $scopeConfig;

    /**
     * @var ConfigurableTypeResourceModel
     */
    private $configurable;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductMetaData
     */
    private $productMetaData;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

   /**
    * ProductDataMapper constructor.
    * @param Logger $logger
    * @param JsonSerializer $jsonSerializer
    * @param GuzzleClient $guzzleClient
    * @param StoreManager $storeManager
    * @param LinkManagement $linkManagement
    * @param CategoryCollectionFactory $categoryCollectionFactory
    * @param BlockFactory $blockFactory
    * @param AppEmulation $appEmulation
    * @param LocaleResolver $localeResolver
    * @param ScopeConfig $scopeConfig
    * @param ConfigurableTypeResourceModel $configurable
    * @param ProductFactory $productFactory
    * @param ProductRepository $productRepository
    * @param SearchCriteriaBuilder $searchCriteriaBuilder
    * @param ProductMetaData $productMetadata
    * @param Configuration $configuration
    * @param SyncSkuManager $syncSkuManager
    * @param CountryFactory $countryFactory
    * @return void
    */
    public function __construct(
        Logger $logger,
        JsonSerializer $jsonSerializer,
        GuzzleClient $guzzleClient,
        StoreManager $storeManager,
        LinkManagement $linkManagement,
        CategoryCollectionFactory $categoryCollectionFactory,
        BlockFactory $blockFactory,
        AppEmulation $appEmulation,
        LocaleResolver $localeResolver,
        ScopeConfig $scopeConfig,
        ConfigurableTypeResourceModel $configurable,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductMetaData $productMetadata,
        Configuration $configuration,
        SyncSkuManager $syncSkuManager,
        CountryFactory $countryFactory
    ) {
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->guzzleClient = $guzzleClient;
        $this->storeManager = $storeManager;
        $this->linkManagement = $linkManagement;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->blockFactory = $blockFactory;
        $this->appEmulation = $appEmulation;
        $this->localeResolver = $localeResolver;
        $this->scopeConfig = $scopeConfig;
        $this->configurable = $configurable;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productMetaData = $productMetadata;
        $this->configuration = $configuration;
        $this->syncSkuManager = $syncSkuManager;
        $this->countryFactory = $countryFactory;
    }

    /**
     * Converts Magento weight unit to Flow weight unit.
     * @param string $weightUnit
     * @return int|string
     */
    private function convertWeightUnit($weightUnit)
    {
        foreach (self::FLOW_UNIT_MEASUREMENTS as $flowWeightUnit => $magentoWeightUnits) {
            if (in_array($weightUnit, $magentoWeightUnits)) {
                return $flowWeightUnit;
            }
        }
        return $weightUnit;
    }

    /**
     * Converts product to Flow item-form.
     * https://docs.flow.io/type/item-form
     * @param SyncSku $syncSku
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     * @return array An array of item-form elements
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function map(SyncSku $syncSku, ProductInterface $product, ProductInterface $parentProduct = null)
    {
        $this->logger->info('Converting product to Flow data: ' . $product->getSku());

        $attributes = $this->getProductAttributeMap($product, $parentProduct);
        $itemData = [
            'number' => $product->getSku(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'locale' => $this->localeResolver->getLocale(),
            'price' => round($this->getProductPrice($product, $attributes), 4),
            'currency' => $this->storeManager->getStore()->getCurrentCurrencyCode(),
            'categories' => $this->getProductCategoryNames($product),
            'attributes' => $attributes,
            'images' => $this->getProductImageData($syncSku, $product),
            'dimensions' => $this->getProductDimensionData($product),
        ];

        $data = [$itemData];

        if ($product->getTypeId() === ConfigurableType::TYPE_CODE && $syncSku->isShouldSyncChildren()) {
            $storeId = $syncSku->getStoreId();
            $children = $this->linkManagement->getChildren($product->getSku());
            foreach ($children as $child) {
                $this->syncSkuManager->enqueueMultipleProductsByProductSku(array($child->getSku()), $storeId);
            }
        }

        return $data;
    }

    /**
     * Returns Flow product dimension data.
     * https://docs.flow.io/type/dimension
     * @param ProductInterface $product
     * @return array|null
     */
    private function getProductDimensionData(ProductInterface $product)
    {
        $return = null;
        if ($product->getWeight()) {
            $weightUnit = $this->scopeConfig->getValue(
                'general/locale/weight_unit',
                StoreScope::SCOPE_STORE
            );
            $return = [
                'product' => [
                    'weight' => [
                        'value' => $product->getWeight(),
                        'units' => $this->convertWeightUnit($weightUnit),
                    ],
                ],
            ];
        }
        return $return;
    }

    /**
     * Returns the public image url for the product by image type.
     * @param SyncSku $syncSku
     * @param ProductInterface $product
     * @param string $imageType
     * @return string|null
     */
    private function getImageUrl(SyncSku $syncSku, ProductInterface $product, $imageType = '')
    {
        $return = null;
        $storeId = $syncSku->getStoreId();
        try {
            $this->appEmulation->startEnvironmentEmulation($storeId, AppArea::AREA_FRONTEND, true);
            $imageBlock = $this->blockFactory->createBlock(ListProductBlock::class);
            $productImage = $imageBlock->getImage($product, $imageType);
            $return = $productImage->getImageUrl();
        } catch (\Exception $e) {
            // Ignore any exceptions here, just return imageUrl as null.

        } finally {
            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $return;
    }

    /**
     * Returns Magento version
     * @return string
     */
    private function getMagentoVersion()
    {
        return $this->productMetaData->getVersion();
    }

    /**
     * Returns an array of category names for the specified product.
     * @param ProductInterface $product
     * @return string[]
     * @throws LocalizedException
     */
    private function getProductCategoryNames(ProductInterface $product)
    {
        $return = [];

        if ($categoryIds = $product->getCategoryIds()) {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addIsActiveFilter();
            $collection->addAttributeToFilter('entity_id', $categoryIds);
            /** @var \Magento\Catalog\Model\Category $category */
            foreach ($collection as $category) {
                $parentCategories = $category->getParentCategories();
                $categoriesNames = [];
                foreach ($parentCategories as $parentCategory) {
                    array_push($categoriesNames, $parentCategory->getName());
                }

                if ($categoriesNames) {
                    array_push($return, implode(' > ', $categoriesNames));
                }
            }
        }
        return $return;
    }

    /**
     * Returns an array of image data for specified product.
     * https://docs.flow.io/type/image-form
     * @param SyncSku $syncSku
     * @param ProductInterface $product
     * @return string[]
     */
    private function getProductImageData(SyncSku $syncSku, ProductInterface $product)
    {
        $images = [];

        if ($thumbImgUrl = $this->getImageUrl($syncSku, $product, 'product_page_image_small')) {
            array_push($images, ['url' => $thumbImgUrl, 'tags' => ['thumbnail']]);
        }

        if ($checkoutImgUrl = $this->getImageUrl($syncSku, $product, 'product_page_image_medium')) {
            array_push($images, ['url' => $checkoutImgUrl, 'tags' => ['checkout']]);
        }

        return $images;
    }

    /**
     * Returns the price for the product or the min price of children if the
     * product is configurable.
     * @param ProductInterface $product
     * @param array $attributes
     * @return float|null
     */
    private function getProductPrice(ProductInterface $product, $attributes)
    {
        $price = 0.0;
        if ($this->configuration->isRegularPricingOverride() && isset($attributes['regular_price'])) {
            $price = $attributes['regular_price'];
        } else if ($product->getTypeId() == ConfigurableType::TYPE_CODE && isset($attributes['minimal_price'])) {
            $price = $attributes['minimal_price'];
        } else if (isset($attributes['final_price'])) {
            $price = $attributes['final_price'];
        }
        return $price;
    }

    /**
     * Returns a map of product attributes.
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     * @return array
     */
    private function getProductAttributeMap(ProductInterface $product, ProductInterface $parentProduct = null)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $data = [];

        // Add product attributes
        $attributes = $product->getAttributes();
        foreach ($attributes as $attr) {
            try {
                if ($frontEnd = $attr->getFrontend()) {
                    if ($attrValue = $frontEnd->getValue($product)) {
                        if (!is_array($attrValue)) {
                            $data[$attr->getAttributeCode()] = (string)$attrValue;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip attributes that throw an error retrieving the front end value.
                // Example: quantity_and_stock_status
            }
        }

        if ($product->getTypeId() == ConfigurableType::TYPE_CODE) {
            // For configurable products, we want to add children attr options.
            // Example:
            //
            // children_attribute_labels = ['Ring Size', 'Color']
            // children_attribute_codes = ['ring_size', 'color']
            // children_attribute_ring_size = [5.0, 5.5, 6.0]
            // children_attribute_color = ['white', 'silver', 'gold']

            $attrLabels = [];
            $attrCodes = [];

            $configurableTypeInstance = $product->getTypeInstance();
            $configData = $product->getTypeInstance()->getConfigurableOptions($product);
            foreach ($configData as $attr) {
                $label = null;
                $code = null;
                $values = [];

                foreach ($attr as $option) {
                    // label and code are the same for all options
                    $label = $option['super_attribute_label'];
                    $code = $option['attribute_code'];

                    if (!in_array($option['option_title'], $values)) {
                        array_push($values, $option['option_title']);
                    }
                }

                array_push($attrLabels, $label);
                array_push($attrCodes, $code);
                $data['children_attribute_' . $code] = $this->jsonSerializer->serialize($values);
            }

            $data['children_attribute_labels'] = $this->jsonSerializer->serialize($attrLabels);
            $data['children_attribute_codes'] = $this->jsonSerializer->serialize($attrCodes);

            // Loading children products
            $childrenProductIds = $configurableTypeInstance->getChildrenIds($product->getId());
            $childrenProductIds = array_values(array_pop($childrenProductIds));
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('entity_id', $childrenProductIds, 'in')
                ->create();
            $childrenProducts = $this->productRepository->getList($searchCriteria);

            $childrenSkus = [];
            foreach ($childrenProducts->getItems() as $childProduct) {
                array_push($childrenSkus, $childProduct->getSku());
            }
            $data['children_product_skus'] = $this->jsonSerializer->serialize($childrenSkus);

        } else {
            // Add parent sku
            if ($parentProduct) {
                $data['parent_sku'] = $parentProduct->getSku();
                $data['parent_entity_id'] = $parentProduct->getId();
            } else {
                $productIds = $this->configurable->getParentIdsByChild($product->getId());
                if (isset($productIds[0])) {
                    $this->logger->info('Found parent product Id: ' . $productIds[0]);
                    $parentProduct = $this->productFactory->create()->load($productIds[0]);
                    $data['parent_sku'] = $parentProduct->getSku();
                    $data['parent_entity_id'] = $parentProduct->getId();
                }
            }
        }

        // Add product_id for harmonization service (same as entity_id)
        $data['product_id'] = $product->getId();

        // Add user agent
        $data['user_agent'] = $this->configuration->getFlowClientUserAgent();

        // Add magento version
        $data['magento_version'] = $this->getMagentoVersion();

        $hostname = getHostName();

        // Add host name
        $data['host_name'] = $hostname;

        // Add host IP
        $data['host_ip'] = getHostByName($hostname);

        $storeId = $product->getStoreId();

        // Add store id
        $data['store_id'] = $storeId;

        // Add base url
        $data['base_url'] = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);

        // Add all pricing information
        if ($product->getPriceInfo()) {
            foreach ($product->getPriceInfo()->getPrices() as $price) {
                if ($price->getValue()) {
                    $roundedAmount = round($price->getValue(), 4);
                    $data[$price->getPriceCode()] = "{$roundedAmount}";
                }
            }
        }

        // Add country of origin
        if (($countryOfManufactureISO2 = $product->getCountryOfManufacture())) {
            try {
                $countryOfManufactureISO3 = $this->getCountryOfManufactureISO3ByISO2($countryOfManufactureISO2);

                $data['country_of_origin'] = $countryOfManufactureISO3;
                $data['country_of_manufacture'] = $countryOfManufactureISO3;
            } catch(Exception $e) {
                $this->logger->info(__('Unable to map country of origin due to exception %1', $e->getMessage()));
                $this->logger->critical($e);

                if(isset($data['country_of_origin'])) {
                    // Unset to avoid error message due to country_of_origin not being ISO3, if such attribute exists.
                    unset($data['country_of_origin']);
                }

                if(isset($data['country_of_manufacture'])) {
                    // Unset to avoid error message due to country_of_manufacture not being ISO3, if such attribute exists.
                    unset($data['country_of_manufacture']);
                }
            }
        }

        return $data;
    }

    /**
     * @param string $countryOfManufactureISO2
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCountryOfManufactureISO3ByISO2(string $countryOfManufactureISO2): string
    {
        $country = $this->countryFactory->create();
        $country->loadByCode($countryOfManufactureISO2);

        if(!$country || !$country->getId() || !($countryOfManufactureISO3 = $country->getData('iso3_code'))) {
            throw new NoSuchEntityException(__('The country %1 that was requested doesn\'t exist.', $countryOfManufactureISO2));
        }

        return $countryOfManufactureISO3;
    }
}
