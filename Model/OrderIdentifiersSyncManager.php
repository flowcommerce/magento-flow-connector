<?php
declare(strict_types=1);

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Bulk\BulkManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory;
use FlowCommerce\FlowConnector\Api\OrderIdentifiersSyncManagerInterface;
use FlowCommerce\FlowConnector\Model\Api\Order\SyncOrderIdenifiers as Client;
use InvalidArgumentException;

/**
 * @package FlowCommerce\FlowConnector\Model
 */
class OrderIdentifiersSyncManager implements OrderIdentifiersSyncManagerInterface
{
    /**
     * @var Logger;
     */
    private $logger;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param Logger $logger
     * @param Configuration $config
     * @param Client $client
     * @param BulkManagementInterface $bulkManagement
     * @param OperationInterfaceFactory $operationFactory
     * @param IdentityGeneratorInterface $identityService
     * @param SerializerInterface $serializer
     * @param UserContextInterface $userContext
     * @return void
     */
    public function __construct(
        Logger $logger,
        Configuration $config,
        Client $client,
        BulkManagementInterface $bulkManagement,
        OperationInterfaceFactory $operationFactory,
        IdentityGeneratorInterface $identityService,
        SerializerInterface $serializer,
        UserContextInterface $userContext
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->client = $client;
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operationFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->userContext = $userContext;
    }

    /**
     * @param int $storeId
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function initialize(int $storeId): void
    {
        $enabled = $this->config->isFlowEnabled($storeId);
        if(!$enabled) {
            throw new LocalizedException(__('Flow Connector is disabled. Aborting.'));
        }

        $syncEnabled = $this->config->isOrderIdentifiersSyncEnabled($storeId);
        if(!$syncEnabled) {
            throw new LocalizedException(__('Order identifiers sync is disabled. Aborting.'));
        }
    }

    /**
     * Queues Magento and Flow order IDs for sycn with Flow API.
     *
     * @param int $storeId
     * @param array $magentoFlowOrderIds
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function queueOrderIdentifiersforSync(int $storeId, array $magentoFlowOrderIds): void
    {
        if($magentoFlowOrderIds) {
            $this->logger->info(sprintf('%s: Number of items to process: %d', __FUNCTION__, count($magentoFlowOrderIds)));
            $this->logger->info(sprintf('%s: %s', __FUNCTION__, var_export($magentoFlowOrderIds, true)));
            $this->initialize($storeId);
            $this->publishOrderIdentifiers($storeId, $magentoFlowOrderIds);
        }
    }

    /**
     * Syncs Magento and Flow order IDs using POST request towards Flow API.
     *
     * @param int $storeId
     * @param array $magentoFlowOrderIds
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function syncOrderIdentifiers(
        int $storeId,
        array $magentoFlowOrderIds
    ): void {
        $this->initialize($storeId);

        foreach($magentoFlowOrderIds as $magentoOrderIncrementId => $flowOrderId) {
            $this->logger->info(
                sprintf(
                    '%s: Store ID %d, Magento Order ID: %s, Flow Order ID: %s',
                    __FUNCTION__,
                    $storeId,
                    $magentoOrderIncrementId,
                    $flowOrderId
                )
            );

            try {
                $response = $this->client->execute($storeId, $magentoOrderIncrementId, $flowOrderId);
                $this->logger->debug(sprintf('%s: Response %s', __FUNCTION__, var_export($response, true)));
            } catch (Exception $e) {
                $this->logger->info(sprintf('%s: Exception %s', __FUNCTION__, $e->getMessage()));
                $this->logger->critical($e);
            }
        }
    }

    /**
     * Schedule new order identifiers bulk for sync.
     *
     * $magentoFlowOrderIds is array in the format:
     *
     * [ $magentoFlowOrderId => $flowOrderId ]
     *
     * @param int $storeId
     * @param array $magentoFlowOrderIds
     * @return void
     * @throws InvalidArgumentException
     * @throws LocalizedException
     */
    private function publishOrderIdentifiers(
        int $storeId,
        array $magentoFlowOrderIds
    ): void {
        $bulkUuid = $this->identityService->generateId();
        $bulkDescription = __('Sync %1 order identifiers.', count($magentoFlowOrderIds));
        $operations = [];

        foreach ($magentoFlowOrderIds as $magentoOrderIncrementId => $flowOrderId) {
            $operations[] = $this->makeSyncOrderIdentifiersOperation(
                'Sync order identifiers',
                'flowcommerce_flowconnector.sync_order_identifiers',
                $storeId,
                (string) $magentoOrderIncrementId,
                (string) $flowOrderId,
                $bulkUuid
            );
        }

        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $bulkUuid,
                $operations,
                $bulkDescription,
                $this->userContext->getUserId()
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while scheduling order identifiers sync.')
                );
            }
        }
    }

    /**
     * Construct single order identifiers sync queue entry.
     *
     * @param string $meta
     * @param string $queue
     * @param int $storeId
     * @param string $magentoOrderIncrementId
     * @param string $flowOrderId
     * @param string $bulkUuid
     * @return OperationInterface
     * @throws InvalidArgumentException
     */
    private function makeSyncOrderIdentifiersOperation(
        string $meta,
        string $queue,
        int $storeId,
        string $magentoOrderIncrementId,
        string $flowOrderId,
        string $bulkUuid
    ): OperationInterface {
        $dataToEncode = [
            'meta_information' => $meta,
            'magento_order_increment_id' => $magentoOrderIncrementId,
            'flow_order_id' => $flowOrderId,
            'store_id' => $storeId
        ];

        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => $queue,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }
}
