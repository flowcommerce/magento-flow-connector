<?php

namespace Flow\FlowConnector\Model\Sync;

use Magento\Framework\UrlInterface;
use Zend\Http\{
    Client,
    Request
};
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Flow\FlowConnector\Exception\CatalogSyncException;
use Flow\FlowConnector\Model\SyncSku;

/**
 * Main class for syncing product data to Flow.
 */
class CatalogSync {

    // Flow unit measurements mapped to abbreviations.
    // https://docs.flow.io/type/unit-of-measurement
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
        'pound' => ['lb','lbs']
    ];

    // Event name that is triggered after a product sync to Flow.
    const EVENT_FLOW_PRODUCT_SYNC_AFTER = 'flow_product_sync_after';

    protected $logger;
    protected $jsonHelper;
    protected $util;
    protected $storeManager;
    protected $objectManager;
    protected $linkManagement;
    protected $categoryCollectionFactory;
    protected $blockFactory;
    protected $appEmulation;
    protected $imageFactory;
    protected $productCollectionFactory;
    protected $mediaGallery;
    protected $localResolver;
    protected $scopeConfig;
    protected $configurable;
    protected $productFactory;
    protected $productRepository;
    protected $imageBuilder;
    protected $eventManager;
    protected $syncSkuFactory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Flow\FlowConnector\Model\Util $util,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\ConfigurableProduct\Api\LinkManagementInterface $linkManagement,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Catalog\Model\Product\ImageFactory $imageFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface $mediaGallery,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Flow\FlowConnector\Model\SyncSkuFactory $syncSkuFactory
    ) {
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->util = $util;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->linkManagement = $linkManagement;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->blockFactory = $blockFactory;
        $this->appEmulation = $appEmulation;
        $this->imageFactory = $imageFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->mediaGallery = $mediaGallery;
        $this->localeResolver = $localeResolver;
        $this->scopeConfig = $scopeConfig;
        $this->configurable = $configurable;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->imageBuilder = $imageBuilder;
        $this->eventManager = $eventManager;
        $this->syncSkuFactory = $syncSkuFactory;
    }

    /**
     * Set the logger (used by console command).
     */
    public function setLogger($logger) {
        $this->logger = $logger;
    }

    /**
     * Queue product for syncing to Flow.
     * @return \Flow\FlowConnector\Model\SyncSku
     */
    public function queue($product) {
        $syncSku = $this->syncSkuFactory->create();

        // Check if product is queued and unprocessed.
        $collection = $syncSku->getCollection()
        ->addFieldToFilter('sku', $product->getSku())
        ->addFieldToFilter('status', SyncSku::STATUS_NEW)
        ->setPageSize(1);

        // Only queue if product is not already queued.
        if ($collection->getSize() == 0) {
            $syncSku->setSku($product->getSku());
            $syncSku->save();
            $this->logger->info('Queued product for sync: ' . $product->getSku());
        } else {
            $this->logger->info('Product already queued, skipping: ' . $product->getSku());
        }
    }

    /**
     * Queue all products for sync to Flow catalog.
     */
    public function queueAll() {
        $this->logger->info("Queueing all products for sync to Flow.");

        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = 'insert into flow_connector_sync_skus(sku, status) select sku, \'' . SyncSku::STATUS_NEW . '\' from catalog_product_entity';
        $connection->query($sql);
    }

    /**
     * Process the SyncSku queue.
     * @param numToProcess Number of records to process.
     * @param keepAlive Number of seconds to keep alive after/between processing.
     */
    public function process($numToProcess = 1000, $keepAlive = 60) {
        $this->logger->info('Starting sync sku processing');

        $this->deleteOldQueueDoneItems();
        $this->resetOldQueueProcessingItems();

        while ($keepAlive > 0) {
            while($numToProcess > 0) {
                $syncSku = $this->getNextUnprocessedEvent();
                if ($syncSku == null) {
                    break;
                }

                $this->logger->info('Processing sync sku: id=' . $syncSku->getId() . ', sku=' . $syncSku->getSku());

                $syncSku->setStatus(SyncSku::STATUS_PROCESSING);
                $syncSku->save();

                // Load product to process
                $product = null;
                try {
                    $product = $this->productRepository->get($syncSku->getSku());
                } catch (NoSuchEntityException $e) {
                    // product does not exist
                }

                // Product sync or delete
                try {
                    if ($product) {
                        $this->logger->info('Syncing sku to Flow: ' . $product->getSku());
                        $this->syncProduct($product);
                        $syncSku->setStatus(SyncSku::STATUS_DONE);
                        $syncSku->save();

                        // Fire an event for client extension code to process
                        $eventName = self::EVENT_FLOW_PRODUCT_SYNC_AFTER;
                        $this->logger->info('Firing event: ' . $eventName);
                        $this->eventManager->dispatch($eventName, [
                            'product' => $product,
                            'logger' => $this->logger
                        ]);

                    } else {
                        $this->logger->info('Deleting sku from Flow: ' . $syncSku->getSku());
                        $this->deleteProduct($syncSku);
                        $syncSku->setStatus(SyncSku::STATUS_DONE);
                        $syncSku->save();
                    }

                } catch (\Exception $e) {
                    $this->logger->warning('Error syncing product ' . $product->getSku() . ': ' . $e->getMessage() . '\n' . $e->getTraceAsString());
                    $syncSku->setStatus(SyncSku::STATUS_ERROR);
                    $syncSku->setMessage(substr($e->getMessage(), 0, 200));
                    $syncSku->save();
                }

                $numToProcess -= 1;
            }

            // $this->logger->info('Sync sku keep alive remaining: ' . $keepAlive);
            $keepAlive -= 1;
            sleep(1);
        }

        $this->logger->info('Done processing sync skus.');
    }

    /**
     * Syncs the specified product to the Flow catalog.
     */
    public function syncProduct($product) {
        if (! $this->util->isFlowEnabled()) {
            throw new CatalogSyncException('Flow module is disabled.');
        }

        $data = $this->convertProductToFlowData($product);

        foreach ($data as $item) {
            $urlStub = '/catalog/items/' . $item['number'];

            $itemStr = $this->jsonHelper->jsonEncode($item);
            $this->logger->info('Syncing item: ' . $itemStr);

            $client = $this->util->getFlowClient($urlStub);
            $client->setMethod(Request::METHOD_PUT);
            $client->setRawBody($itemStr);

            $response = $client->send();
            if ($response->isSuccess()) {
                $this->logger->info('CatalogSync->syncProduct: success');
                $this->logger->info('Status code: ' . $response->getStatusCode());
                $this->logger->info('Body: ' . $response->getBody());
            } else {
                $this->logger->error('CatalogSync->syncProduct: failed');
                $this->logger->error('Status code: ' . $response->getStatusCode());
                $this->logger->error('Body: ' . $response->getBody());
                throw new CatalogSyncException('Failed to sync product to flow: ' . $product->getSku() . ': ' . $response->getBody());
            }
        }
    }

    /**
     * Deletes the sku from Flow.
     */
    protected function deleteProduct($syncSku) {
        if (! $this->util->isFlowEnabled()) {
            throw new CatalogSyncException('Flow module is disabled.');
        }

        $client = $this->util->getFlowClient('/catalog/items/' . $syncSku->getSku());
        $client->setMethod(Request::METHOD_DELETE);
        $response = $client->send();

        if ($response->isSuccess()) {
            $this->logger->info('Sucessfully deleted sku from Flow: ' . $syncSku->getSku());
        } else {
            throw new CatalogSyncException('Unable to delete: ' . $response->getContent());
        }
    }

    /**
     * Converts product to Flow item-form.
     *
     * https://docs.flow.io/type/item-form
     *
     * @return array An array of item-form elements
     */
    protected function convertProductToFlowData($product, $parentProduct = null) {
        $this->logger->info('Converting product to Flow data: ' . $product->getSku());

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
             $children = $this->linkManagement->getChildren($product->getSku());
             $data = [];
             foreach($children as $child) {
                 $data = array_merge($data, $this->convertProductToFlowData($child, $product));
             }
             return $data;
        }

        $itemData = [
            "number" => $product->getSku(),
            "name" => $product->getName(),
            "description" => $product->getDescription(),
            "locale" => $this->localeResolver->getLocale(),
            "price" => $product->getPrice(),
            "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode(),
            "categories" => $this->getProductCategoryNames($product),
            "attributes" => $this->getProductAttributeMap($product, $parentProduct),
            "images" => $this->getProductImageData($product),
            "dimensions" => $this->getProductDimensionData($product)
        ];

        return [$itemData];
    }

    /**
     * Returns an array of category names for the specified product.
     */
    protected function getProductCategoryNames($product) {
        $catNames = [];

        if ($categoryIds = $product->getCategoryIds()) {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addIsActiveFilter();
            $collection->addAttributeToFilter('entity_id', $categoryIds);
            foreach($collection as $category) {
                array_push($catNames, $category->getName());
            }
        }
        return $catNames;
    }

    /**
     * Returns an array of image data for specified product.
     * https://docs.flow.io/type/image-form
     */
    protected function getProductImageData($product) {
        return [
            [
                'url' => $this->getImageUrl($product, 'product_page_image_small'),
                'tags' => ['thumbnail']
            ],
            [
                'url' => $this->getImageUrl($product, 'product_page_image_medium'),
                'tags' => ['checkout']
            ]
        ];
    }

    /**
     * Returns the public image url for the product by image type.
     */
    protected function getImageUrl($product, string $imageType = '') {
        $storeId = $this->storeManager->getStore()->getId();

        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $imageBlock = $this->blockFactory->createBlock('Magento\Catalog\Block\Product\ListProduct');
        $productImage = $imageBlock->getImage($product, $imageType);
        $imageUrl = $productImage->getImageUrl();

        $this->appEmulation->stopEnvironmentEmulation();

        return $imageUrl;
    }

    /**
     * Returns a map of product attributes.
     */
    protected function getProductAttributeMap($product, $parentProduct = null) {
        $attributes = $product->getAttributes();

        $data = [];
        foreach($attributes as $attr) {
            try {
                if ($attrValue = $attr->getFrontend()->getValue($product)) {
                    if (!is_array($attrValue)) {
                        $data[$attr->getAttributeCode()] = (string)$attrValue;
                    }
                }
            } catch (\Exception $e) {
                // Skip attributes that throw an error retrieving the front end value.
                // Example: quantity_and_stock_status
            }
        }

        if ($parentProduct) {
            $data['parent_sku'] = $parentProduct->getSku();

        } else {
            $productIds = $this->configurable->getParentIdsByChild($product->getId());
            if (isset($productIds[0])) {
                $this->logger->info('Found parent product Id: ' . $productIds[0]);
                $parentProduct = $this->productFactory->create()->load($productIds[0]);
                $data['parent_sku'] = $parentProduct->getSku();
            }
        }

        return $data;
    }

    /**
     * Returns Flow product dimension data.
     * https://docs.flow.io/type/dimension
     */
    protected function getProductDimensionData($product) {
        if ($product->getWeight()) {
            $weightUnit = $this->scopeConfig->getValue(
                'general/locale/weight_unit',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            return [
                'product' => [
                    'weight' => [
                        'value' => $product->getWeight(),
                        'units' => $this->convertWeightUnit($weightUnit)
                    ]
                ]
            ];
        } else {
            return null;
        }
    }

    /**
     * Converts Magento weight unit to Flow weight unit.
     */
    protected function convertWeightUnit($weightUnit) {
        foreach(self::FLOW_UNIT_MEASUREMENTS as $k => $v)  {
            if (in_array($weightUnit, $v)) {
                return $k;
            }
        }
        return $weightUnit;
    }

    /**
     * Returns the next unprocessed event.
     */
    private function getNextUnprocessedEvent() {
        $collection = $this->syncSkuFactory->create()->getCollection();
        $collection->addFieldToFilter('status', SyncSku::STATUS_NEW);
        $collection->setOrder('priority', 'ASC');
        $collection->setOrder('updated_at', 'ASC');
        $collection->setPageSize(1);
        if ($collection->getSize() == 0) {
            return null;
        } else {
            return $collection->getFirstItem();
        }
    }

    /**
     * Deletes old processed items.
     */
    private function deleteOldQueueDoneItems() {
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = 'delete from flow_connector_sync_skus where status=\'' . SyncSku::STATUS_DONE . '\' and updated_at < date_sub(now(), interval 96 hour)';
        $connection->query($sql);
    }

    /**
     * Reset any items that have been stuck processing for too long.
     */
    private function resetOldQueueProcessingItems() {
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = 'update flow_connector_sync_skus set status=\'' .  SyncSku::STATUS_NEW . '\' where status=\'' . SyncSku::STATUS_PROCESSING . '\' and updated_at < date_sub(now(), interval 4 hour)';
        $connection->query($sql);
    }
}
