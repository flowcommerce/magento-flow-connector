<?php

namespace FlowCommerce\FlowConnector\Plugin\Magento\Catalog\Model\Indexer\Product;

use FlowCommerce\FlowConnector\Model\SyncSkuManager;
use Magento\Catalog\Model\Indexer\Product\Price as PriceIndexer;
use Magento\Framework\FlagManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Price
 * @package FlowCommerce\FlowConnector\Plugin\Catalog\Model\Indexer\Product
 */
class Price
{
    /**
     * Schedule full price sync flag code
     */
    const SCHEDULE_FULL_SYNC_FLAG_CODE = 'flowconnector_full_sync_after_price_reindex';

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SyncSkuManager
     */
    private $syncSkuManager;

    /**
     * Price constructor.
     * @param FlagManager $flagManager
     * @param Logger $logger
     * @param SyncSkuManager $syncSkuManager
     */
    public function __construct(
        FlagManager $flagManager,
        Logger $logger,
        SyncSkuManager $syncSkuManager
    ) {
        $this->flagManager = $flagManager;
        $this->logger = $logger;
        $this->syncSkuManager = $syncSkuManager;
    }

    /**
     * After a full price indexing, we need to schedule a full sync to flow
     * @param PriceIndexer $indexer
     * @param mixed $result
     * @return mixed $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteFull(PriceIndexer $indexer, $result)
    {
        if ($this->shouldScheduleFullFlowSync()) {
            $this->logger->info('Full price reindex executed. Adding all products to Flow sync.');
            $this->syncSkuManager->enqueueAllProducts();
            $this->flagManager->saveFlag(self::SCHEDULE_FULL_SYNC_FLAG_CODE, false);
        }
        return $result;
    }

    public function scheduleFlowFullSync()
    {
        $this->flagManager->saveFlag(self::SCHEDULE_FULL_SYNC_FLAG_CODE, true);
    }

    /**
     * Checks if a catalog rule has been created/modified since last reindex
     * @return bool
     */
    private function shouldScheduleFullFlowSync()
    {
        return (bool) $this->flagManager->getFlagData(self::SCHEDULE_FULL_SYNC_FLAG_CODE);
    }
}
