<?php

namespace FlowCommerce\FlowConnector\Model\Sync;

use FlowCommerce\FlowConnector\Api\Data\SyncSkuInterface;
use FlowCommerce\FlowConnector\Api\Data\SyncSkuSearchResultsInterface as SearchResultInterface;
use FlowCommerce\FlowConnector\Api\SyncSkuManagementInterface as SyncSkuManager;
use FlowCommerce\FlowConnector\Model\Api\Item\Delete as FlowDeleteItemApi;
use FlowCommerce\FlowConnector\Model\Api\Item\Save as FlowSaveItemApi;
use FlowCommerce\FlowConnector\Model\Configuration;
use FlowCommerce\FlowConnector\Model\Notification;
use FlowCommerce\FlowConnector\Model\SyncSku;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface as Logger;

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
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * CatalogSync constructor.
     * @param Logger $logger
     * @param JsonSerializer $jsonSerializer
     * @param Configuration $configuration
     * @param EventManager $eventManager
     * @param SyncSkuManager $syncSkuManager
     * @param FlowSaveItemApi $flowSaveItemApi
     * @param FlowDeleteItemApi $flowDeleteItemApi
     * @param Notification $notification
     */
    public function __construct(
        Logger $logger,
        JsonSerializer $jsonSerializer,
        Configuration $configuration,
        EventManager $eventManager,
        SyncSkuManager $syncSkuManager,
        FlowSaveItemApi $flowSaveItemApi,
        FlowDeleteItemApi $flowDeleteItemApi,
        Notification $notification
    ) {
        $this->eventManager = $eventManager;
        $this->flowDeleteItemApi = $flowDeleteItemApi;
        $this->flowSaveItemApi = $flowSaveItemApi;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
        $this->configuration = $configuration;
        $this->notification = $notification;
    }

    /**
     * Marks SyncSku as error
     * @param string $reason
     * @param $index
     */
    public function failureProductDelete($reason, $index)
    {
        if (array_key_exists($index, $this->syncSkusToDelete)) {
            $syncSku = $this->syncSkusToDelete[$index];
            $this->syncSkuManager->markSyncSkuAsError(
                $syncSku,
                $reason,
                $syncSku->getData('flow_request_url')
            );
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
            $syncSku = $this->syncSkusToUpdate[$index];
            $this->syncSkuManager->markSyncSkuAsError(
                $syncSku,
                $reason,
                $syncSku->getData('flow_request_url'),
                $syncSku->getData('flow_request_body')
            );
            unset($this->syncSkusToUpdate[$index]);
        }
    }

    /**
     * Processes the SyncSku queue.
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process()
    {
        try {
            $this->logger->info('Starting sync sku processing');

            $ts = microtime(true);
            $syncSkus = $this->syncSkuManager->getNextSyncSkuBatchToBeProcessed(1000);
            $this->logger->info('Time to load products to sync: ' . (microtime(true) - $ts));

            if ((int)$syncSkus->getTotalCount() === 0) {
                $this->logger->info('No records to process.');
                return;
            }
            $this->syncSkusToUpdate = [];
            $this->syncSkusToDelete = [];

            foreach ($syncSkus->getItems() as $syncSku) {
                $product = $syncSku->getProduct();
                if (!$this->configuration->isFlowEnabled($syncSku->getStoreId())) {
                    $this->syncSkuManager->markSyncSkuAsError($syncSku, 'Flow module is disabled.');
                    continue;
                }
                if ($product) {
                    // Product exists in enabled state and was updated - propagate update to Flow.
                    $this->syncSkuManager->markSyncSkuAsProcessing($syncSku);
                    array_push($this->syncSkusToUpdate, $syncSku);
                } elseif ($syncSku->getState() === SyncSkuInterface::STATE_DONE) {
                    // Product was either deleted or disabled in Magento and we have record of it being
                    // previously synced to Flow - delete from Flow as well, then delete SKU from our table.
                    array_push($this->syncSkusToDelete, $syncSku);
                } else {
                    // Product was either deleted or disabled in Magento and there is no record of it ever
                    // being synced to Flow - Delete SKU from our table.
                    $this->syncSkuManager->deleteSyncSku($syncSku);
                }
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

            $this->logger->info('Done processing sync skus.');

        } catch (\Exception $e) {
            $this->logger->warning('Error syncing products: '
                . $e->getMessage() . '\n' . $e->getTraceAsString());
        }
    }

    /**
     * Marks SyncSku as processed
     * @param HttpResponse $response
     * @param $index
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function successfulProductDelete($response, $index)
    {
        if (array_key_exists($index, $this->syncSkusToDelete)) {
            $syncSku = $this->syncSkusToDelete[$index];
            $this->syncSkuManager->deleteSyncSku(
                $syncSku
            );
            unset($this->syncSkusToDelete[$index]);
        }
    }

    /**
     * Marks SyncSku as processed
     * @param HttpResponse $response
     * @param $index
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function successfulProductSave($response, $index)
    {
        if (array_key_exists($index, $this->syncSkusToUpdate)) {
            $syncSku = $this->syncSkusToUpdate[$index];
            $this->syncSkuManager->markSyncSkuAsDone(
                $syncSku,
                $syncSku->getData('flow_request_url'),
                $syncSku->getData('flow_request_body'),
                $this->jsonSerializer->serialize($response->getHeaders()),
                $response->getBody()
            );
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
        $this->notification->setLogger($logger);
    }
}
