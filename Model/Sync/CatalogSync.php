<?php

namespace FlowCommerce\FlowConnector\Model\Sync;

use FlowCommerce\FlowConnector\Api\Data\SyncSkuSearchResultsInterface as SearchResultInterface;
use FlowCommerce\FlowConnector\Api\SyncSkuManagementInterface as SyncSkuManager;
use FlowCommerce\FlowConnector\Exception\CatalogSyncException;
use FlowCommerce\FlowConnector\Model\Api\Item\Delete as FlowDeleteItemApi;
use FlowCommerce\FlowConnector\Model\Api\Item\Save as FlowSaveItemApi;
use FlowCommerce\FlowConnector\Model\SyncSku;
use FlowCommerce\FlowConnector\Model\SyncSkuFactory;
use FlowCommerce\FlowConnector\Model\Util as FlowUtil;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;
use Zend\Http\Request;

/**
 * Main class for syncing product data to Flow.
 */
class CatalogSync
{
    /**
     * Event name that is triggered after a product sync to Flow.
     */
    const EVENT_FLOW_PRODUCT_SYNC_AFTER = 'flow_product_sync_after';

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var FlowDeleteItemApi
     */
    private $flowDeleteItemApi;

    /**
     * @var FlowSaveItemApi
     */
    private $flowSaveItemApi;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SyncSkuFactory
     */
    private $syncSkuFactory;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * @var SyncSku[]
     */
    private $syncSkusToDelete = [];

    /**
     * @var SyncSku[]
     */
    private $syncSkusToUpdate = [];

    /**
     * @var FlowUtil
     */
    private $util;

    /**
     * CatalogSync constructor.
     * @param Logger $logger
     * @param JsonSerializer $jsonSerializer
     * @param FlowUtil $util
     * @param EventManager $eventManager
     * @param SyncSkuFactory $syncSkuFactory
     * @param SyncSkuManager $syncSkuManager
     * @param FlowSaveItemApi $flowSaveItemApi
     * @param FlowDeleteItemApi $flowDeleteItemApi
     */
    public function __construct(
        Logger $logger,
        JsonSerializer $jsonSerializer,
        FlowUtil $util,
        EventManager $eventManager,
        SyncSkuFactory $syncSkuFactory,
        SyncSkuManager $syncSkuManager,
        FlowSaveItemApi $flowSaveItemApi,
        FlowDeleteItemApi $flowDeleteItemApi
    ) {
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->util = $util;
        $this->eventManager = $eventManager;
        $this->syncSkuFactory = $syncSkuFactory;
        $this->syncSkuManager = $syncSkuManager;
        $this->flowSaveItemApi = $flowSaveItemApi;
        $this->flowDeleteItemApi = $flowDeleteItemApi;
    }

    /**
     * Marks SyncSku as error
     * @param string $reason
     * @param $index
     */
    public function failureProductDelete($reason, $index)
    {
        if (array_key_exists($index, $this->syncSkusToDelete)) {
            $this->syncSkuManager->markSyncSkuAsError($this->syncSkusToDelete[$index], $reason);
            unset($this->syncSkusToDelete[$index]);
        }
    }

    /**
     * Marks SyncSku as error
     * @param string $reason
     * @param $index
     */
    public function failureProductSave($reason, $index)
    {
        if (array_key_exists($index, $this->syncSkusToUpdate)) {
            $this->syncSkuManager->markSyncSkuAsError($this->syncSkusToUpdate[$index], $reason);
            unset($this->syncSkusToUpdate[$index]);
        }
    }

    /**
     * Returns the next SyncSku batch to be processed
     * @param $batchSize
     * @return SearchResultInterface
     */
    private function getNextUnprocessedEvents($batchSize)
    {
        return $this->syncSkuManager->getNextSyncSkuBatchToBeProcessed($batchSize);
    }

    /**
     * Initializes the connector with Flow.
     * @param int $storeId
     * @return bool
     */
    public function initFlowConnector($storeId)
    {
        $data = [
            'intent' => 'price',
            'type' => 'decimal',
            'options' => [
                'required' => false,
                'show_in_catalog' => false,
                'show_in_harmonization' => false,
            ],
        ];

        $priceCodes = [
            'base_price',
            'bundle_option',
            'bundle_selection',
            'catalog_rule_price',
            'configured_price',
            'configured_regular_price',
            'custom_option_price',
            'final_price',
            'link_price',
            'max_price',
            'min_price',
            'msrp_price',
            'regular_price',
            'special_price',
            'tier_price',
        ];

        $isSuccess = true;

        foreach ($priceCodes as $priceCode) {
            $priceData = array_merge($data, ['key' => $priceCode]);
            if (!$this->upsertFlowAttribute($storeId, $priceData)) {
                $isSuccess = false;
            }
        }

        return $isSuccess;
    }

    /**
     * Processes the SyncSku queue.
     * @param int $numToProcess Number of records to process. Pass in -1 to process all records.
     * @param int $keepAlive Number of seconds to keep alive after/between processing.
     * @return void
     */
    public function process($numToProcess = 1000, $keepAlive = 60)
    {
        $this->logger->info('Starting sync sku processing');

        while ($keepAlive > 0) {
            while ($numToProcess != 0) {
                $ts = microtime(true);
                $syncSkus = $this->getNextUnprocessedEvents(100);
                $this->logger->info('Time to load products to sync: ' . (microtime(true) - $ts));

                if ((int)$syncSkus->getTotalCount() === 0) {
                    $this->logger->info('No records to process.');
                    break;
                }

                try {
                    $this->syncSkusToUpdate = [];
                    $this->syncSkusToDelete = [];

                    foreach ($syncSkus->getItems() as $syncSku) {
                        $product = $syncSku->getProduct();
                        if (!$this->util->isFlowEnabled($syncSku->getStoreId())) {
                            throw new CatalogSyncException('Flow module is disabled.');
                        }
                        if ($product) {
                            $this->syncSkuManager->markSyncSkuAsProcessing($syncSku);
                            array_push($this->syncSkusToUpdate, $syncSku);
                        } else {
                            array_push($this->syncSkusToDelete, $syncSku);
                        }

                        $numToProcess -= 1;
                    }

                    if (count($this->syncSkusToUpdate)) {
                        $ts = microtime(true);
                        $this->flowSaveItemApi->execute(
                            $this->syncSkusToUpdate,
                            [$this, 'successfulProductSave'],
                            [$this, 'failureProductSave']
                        );
                        $this->logger->info('Time to asynchronously save products on flow.io: '
                            . (microtime(true) - $ts));
                    }

                    if (count($this->syncSkusToDelete)) {
                        $ts = microtime(true);
                        $this->flowDeleteItemApi->execute(
                            $this->syncSkusToDelete,
                            [$this, 'successfulProductDelete'],
                            [$this, 'failureProductDelete']
                        );
                        $this->logger->info('Time to asynchronously delete products on flow.io: '
                            . (microtime(true) - $ts));
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Error syncing product ' . $syncSku->getSku() . ': '
                        . $e->getMessage() . '\n' . $e->getTraceAsString());
                    $this->syncSkuManager->markSyncSkuAsError($syncSku, $e->getMessage());
                }
            }

            if ($numToProcess == 0) {
                // We've hit the processing limit, break out of loop.
                break;
            }

            // Num to process not exhausted, keep alive to wait for more.
            $keepAlive -= 1;
            sleep(1);
        }

        $this->logger->info('Done processing sync skus.');
    }

    /**
     * Queue product for syncing to Flow.
     * @param ProductInterface $product
     * @return void
     * @throws \Exception
     */
    public function queue(ProductInterface $product)
    {
        /** @var SyncSku $syncSku */
        $syncSku = $this->syncSkuFactory->create();

        // Check if connector is enabled for store
        if (!$this->util->isFlowEnabled($product->getStoreId())) {
            $this->logger->info('Product store does not have Flow enabled, skipping: ' . $product->getSku());

        } else {
            // Check if product is queued and unprocessed.
            $collection = $syncSku->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('sku', $product->getSku())
                ->addFieldToFilter('status', SyncSku::STATUS_NEW)
                ->setPageSize(1);

            // Only queue if product is not already queued.
            $shouldSyncChildren = $this->shouldSyncChildren($product);
            if ($collection->getSize() == 0) {
                $syncSku->setSku($product->getSku());
                $syncSku->setStoreId($product->getStoreId());
                $syncSku->setShouldSyncChildren($shouldSyncChildren);
                $syncSku->save();
                $this->logger->info('Queued product for sync: ' . $product->getSku());
            } else {
                /** @var SyncSku $existingSyncSku */
                $existingSyncSku = $collection->getFirstItem();
                if ($existingSyncSku->isShouldSyncChildren() !== $shouldSyncChildren) {
                    $existingSyncSku->setShouldSyncChildren($shouldSyncChildren);
                    $existingSyncSku->save();
                } else {
                    $this->logger->info('Product already queued, skipping: ' . $product->getSku());
                }
            }
        }
    }

    private function shouldSyncChildren(ProductInterface $product)
    {
        $return = false;
        if ($product->getTypeId() === ConfigurableType::TYPE_CODE) {
            if ($product->isObjectNew()) {
                $return = true;
            } else {
                // We're dealing with an update of a configurable product
                // We only want to sync it's children when the links to it's children have changed
                $originalChildProductIds = [];
                /** @var ProductInterface $childProduct */
                foreach ($product->getTypeInstance()->getUsedProducts($product) as $childProduct) {
                    array_push($originalChildProductIds, $childProduct->getId());
                }
                $currentChildProductIds = (array) $product->getExtensionAttributes()->getConfigurableProductLinks();
                sort($originalChildProductIds);
                sort($currentChildProductIds);
                $return = ($originalChildProductIds !== $currentChildProductIds);
            }
        }

        return $return;
    }

    /**
     * Marks SyncSku as processed
     * @param $response
     * @param $index
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function successfulProductDelete($response, $index)
    {
        if (array_key_exists($index, $this->syncSkusToDelete)) {
            $this->syncSkuManager->deleteSyncSku($this->syncSkusToDelete[$index]);
            unset($this->syncSkusToDelete[$index]);
        }
    }

    /**
     * Marks SyncSku as processed
     * @param $response
     * @param $index
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function successfulProductSave($response, $index)
    {
        if (array_key_exists($index, $this->syncSkusToUpdate)) {
            $syncSku = $this->syncSkusToUpdate[$index];
            $this->syncSkuManager->markSyncSkuAsDone($syncSku);
            // Fire an event for client extension code to process
            $this->logger->info('Firing event: ' . self::EVENT_FLOW_PRODUCT_SYNC_AFTER);
            $this->eventManager->dispatch(self::EVENT_FLOW_PRODUCT_SYNC_AFTER, [
                'product' => $syncSku->getProduct(),
                'logger' => $this->logger,
            ]);
            unset($this->syncSkusToUpdate[$index]);
        }
    }

    /**
     * @param Logger $logger
     * Set the logger (used by console command).
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        $this->util->setLogger($logger);
    }

    /**
     * Helper function to upsert Flow attributes.
     * @param int $storeId
     * @param string[] $data
     * @return bool
     */
    private function upsertFlowAttribute($storeId, $data)
    {
        $urlStub = '/attributes/' . $data['key'];

        $dataStr = $this->jsonSerializer->serialize($data);
        $this->logger->info('Updating Flow attribute: ' . $dataStr);

        $client = $this->util->getFlowClient($urlStub, $storeId);
        $client->setMethod(Request::METHOD_PUT);
        $client->setRawBody($dataStr);

        $response = $this->util->sendFlowClient($client, 3);

        if ($response->isSuccess()) {
            $this->logger->info('CatalogSync->upsertFlowAttribute ' . $urlStub . ': success');
            $this->logger->info('Status code: ' . $response->getStatusCode());
            $this->logger->info('Body: ' . $response->getBody());
        } else {
            $this->logger->error('CatalogSync->upsertFlowAttribute ' . $urlStub . ': failed');
            $this->logger->error('Status code: ' . $response->getStatusCode());
            $this->logger->error('Body: ' . $response->getBody());
        }

        return (bool)$response->isSuccess();
    }
}
