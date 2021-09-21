<?php
declare(strict_types=1);

namespace FlowCommerce\FlowConnector\Model;

use Exception;
use Zend_Db_Adapter_Exception;
use LogicException;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\TemporaryStateExceptionInterface;
use Magento\Framework\Bulk\OperationInterface;
use Magento\Framework\Serialize\SerializerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Magento\AsynchronousOperations\Api\Data\OperationInterface as AsyncOperationInterface;

/**
 * @package FlowCommerce\FlowConnector\Model
 */
class OrderIdentifiersSyncConsumer
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var OrderIdentifiersSyncManager
     */
    private $orderIdentifiersSyncManager;

    /**
     * @param Logger $logger
     * @param SerializerInterface $serializer
     * @param EntityManager $entityManager
     * @param Configuration $config
     * @param OrderIdentifiersSyncManager $orderIdentifiersSyncManager
     * @return void
     */
    public function __construct(
        Logger $logger,
        SerializerInterface $serializer,
        EntityManager $entityManager,
        Configuration $config,
        OrderIdentifiersSyncManager $orderIdentifiersSyncManager
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->orderIdentifiersSyncManager = $orderIdentifiersSyncManager;
    }

    /**
     * @param AsyncOperationInterface $operation
     * @throws GuzzleException
     * @throws LogicException
     * @throws Exception
     * @return void
     */
    public function process(AsyncOperationInterface $operation): void
    {
        try {
            $serializedData = $operation->getSerializedData();
            $bulkUuid = $operation->getBulkUuid();
            $data = $this->serializer->unserialize($serializedData);
            $this->execute($bulkUuid, $data);
        } catch (Zend_Db_Adapter_Exception $e) {
            $this->logger->critical($e->getMessage());
            if ($e instanceof \Magento\Framework\DB\Adapter\LockWaitException
                || $e instanceof \Magento\Framework\DB\Adapter\DeadlockException
                || $e instanceof \Magento\Framework\DB\Adapter\ConnectionException
            ) {
                $status = OperationInterface::STATUS_TYPE_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = $e->getMessage();
            } else {
                $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
                $errorCode = $e->getCode();
                $message = __(
                    'Sorry, something went wrong during order identifier sync. Please see log for details.'
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            $status = ($e instanceof TemporaryStateExceptionInterface)
                ? OperationInterface::STATUS_TYPE_RETRIABLY_FAILED
                : OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $status = OperationInterface::STATUS_TYPE_NOT_RETRIABLY_FAILED;
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during order identifier synce. Please see log for details.');
        }

        $operation->setStatus($status ?? OperationInterface::STATUS_TYPE_COMPLETE)
            ->setErrorCode($errorCode ?? null)
            ->setResultMessage($message ?? null);

        $this->entityManager->save($operation);
    }

    /**
     * @param string $bulkUuid
     * @param array $data
     * @throws GuzzleException
     * @throws LocalizedException
     */
    private function execute(string $bulkUuid, array $data): void
    {
        if(empty($data['store_id']) || empty($data['magento_order_increment_id']) || empty($data['flow_order_id'])) {
            throw new LocalizedException(
                __('Malformed order identifiers sync queue entry with uuid %s. Aborting.', $bulkUuid)
            );
        }

        $this->orderIdentifiersSyncManager ->syncOrderIdentifiers(
            (int)$data['store_id'],
            [
                (string)$data['magento_order_increment_id'] => (string)$data['flow_order_id']
            ]
        );
    }
}
