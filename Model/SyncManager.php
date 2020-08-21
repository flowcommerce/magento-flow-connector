<?php

namespace FlowCommerce\FlowConnector\Model;

use FlowCommerce\FlowConnector\Api\SyncManagementInterface;
use FlowCommerce\FlowConnector\Model\Api\Sync\Stream\Put as SyncStreamPutApiClient;
use FlowCommerce\FlowConnector\Model\Api\Sync\Stream\Record\Put as SyncStreamRecordPutApiClient;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class SyncManager
 * @package FlowCommerce\FlowConnector\Model
 */
class SyncManager implements SyncManagementInterface
{
    /**
     * sync_stream_type for OrderPlacedV2
     */
    const PLACED_ORDER_TYPE = 'placed_order';

    /**
     * Array of expected streams
     */
    const STREAMS = [ PLACED_ORDER_TYPE ];

    /**
     * @var SyncStreamPutApiClient
     */
    private $syncStreamPutApiClient;

    /**
     * @var SyncStreamRecordPutApiClient
     */
    private $syncStreamRecordPutApiClient;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * SyncManager constructor.
     * @param Logger $logger
     * @param StoreManager $storeManager
     */
    public function __construct(
        SyncStreamPutApiClient $syncStreamPutApiClient,
        SyncStreamRecordPutApiClient $syncStreamRecordPutApiClient,
        Logger $logger,
        StoreManager $storeManager,
        Configuration $configuration
    ) {
        $this->notification = $notification;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function registerAllSyncStreams($storeId)
    {
        $return = false;

        $enabled = $this->configuration->isFlowEnabled($storeId);

        if ($enabled) {
            $return = true;
            try {
                $this->logger->info('Registering all Sync Streams...');
                foreach (self::STREAMS as $type) {
                    $result = $this->putSyncStream($storeId, $type);
                    if (!$result) {
                        $return = false;
                    }
                }
            } catch (\Exception $e) {
                $message = sprintf(
                    'Error occurred registering Sync Streams for store %d: %s.',
                    $storeId,
                    $e->getMessage()
                );
                $this->logger->critical($message);
                throw new \Exception($message);
            }
        } else {
            $message = sprintf('Flow connector disabled or missing API credentials for store %d', $storeId);
            $this->logger->notice($message);
            throw new \Exception($message);
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function putSyncStream($storeId, $type)
    {
        $this->logger->info('Registering Sync Stream key: ' . $type);
        return $this->syncStreamPutApiClient->execute($storeId, $type);
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function putSyncStreamRecord($storeId, $type, $value)
    {
        $this->logger->info('Recording value: ' . $value . ' Sync Stream key : ' . $type);
        return $this->syncStreamRecordPutApiClient->execute($storeId, $type, $value);
    }
}

