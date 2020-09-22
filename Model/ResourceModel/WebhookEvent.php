<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\Data\WebhookEventInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class WebhookEvent
 * @package FlowCommerce\FlowConnector\Model\ResourceModel
 */
class WebhookEvent extends AbstractDb
{
    /**
     * Status - New
     */
    const STATUS_NEW = 'new';

    /**
     * Status - Processing
     */
    const STATUS_PROCESSING = 'processing';

    /**
     * Status - Error
     */
    const STATUS_ERROR = 'error';

    /**
     * Status - Done
     */
    const STATUS_DONE = 'done';

    /**
     * Webhook event batch size for updating multiple statuses
     */
    const UPDATE_MULTIPLE_STATUSES_BATCH_SIZE = 50;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * InventorySync constructor.
     * @param DataObjectFactory $dataObjectFactory
     * @param Context $context
     * @param null $connectionName
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Initializes the resource model
     */
    protected function _construct()
    {
        $this->_init('flow_connector_webhook_events', 'id');
    }

    /**
     * Returns default attributes
     * @return string[]
     */
    protected function _getDefaultAttributes()
    {
        return [
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Updates given Webhook Event model data in the database
     * This is a temporary workaround, as some clients were experiencing issues when
     * WebhookEvents were being saved using the model's save method
     * @param WebhookEventInterface $webhookEvent
     * @throws
     */
    public function directQuerySave($webhookEvent)
    {
        $tableName = $this->getMainTable();
        $sql = '
        insert into ' . $tableName . '(
            id,
            status,
            message,
            type,
            payload,
            triggered_at
        ) values (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        ) on duplicate key update
            status = ?,
            message = ?,
            type = ?,
            payload = ?,
            triggered_at = ?
        ';
        $this->getConnection()->query(
            $sql,
            [
                $webhookEvent->getId(),
                $webhookEvent->getStatus(),
                $webhookEvent->getMessage(),
                $webhookEvent->getType(),
                $webhookEvent->getPayload(),
                $webhookEvent->getTriggeredAt(),
                $webhookEvent->getStatus(),
                $webhookEvent->getMessage(),
                $webhookEvent->getType(),
                $webhookEvent->getPayload(),
                $webhookEvent->getTriggeredAt()
            ]
        );
    }

    /**
     * Deletes old processed webhook events.
     * @return void
     * @throws LocalizedException
     */
    public function deleteOldProcessedEvents()
    {
        $tableName = $this->getMainTable();
        $sql = '
        delete from ' . $tableName . '
         where status=\'' . self::STATUS_DONE . '\'
           and updated_at < date_sub(now(), interval 96 hour)
        ';
        $this->getConnection()->query($sql);
    }

    /**
     * Reset any webhook events that have been stuck processing for too long.
     * @return void
     * @throws LocalizedException
     */
    public function resetOldErrorEvents()
    {
        $tableName = $this->getMainTable();
        $sql = '
        update ' . $tableName . '
           set status=\'' . self::STATUS_NEW . '\'
         where status=\'' . self::STATUS_PROCESSING . '\'
           and updated_at < date_sub(now(), interval 4 hour)
        ';
        $this->getConnection()->query($sql);
    }

    /**
     * Updates status for multiple webhook events using direct queries in batches
     * @param WebhookEventInterface[] $webhookEvents
     * @param string $newStatus
     * @throws LocalizedException
     */
    public function updateMultipleStatuses(array $webhookEvents, $newStatus)
    {
        $ids = [];
        if (count($webhookEvents)) {
            foreach ($webhookEvents as $webhookEvent) {
                array_push($ids, $webhookEvent->getId());
            }
            $tableName = $this->getMainTable();

            $object = $this->dataObjectFactory->create();
            $object->setData(WebhookEventInterface::DATA_KEY_STATUS, $newStatus);
            $object->setData(WebhookEventInterface::DATA_KEY_MESSAGE, 'Webhook event type resolved.');
            $preparedData = $this->_prepareDataForTable($object, $tableName);

            $batches = array_chunk($ids, self::UPDATE_MULTIPLE_STATUSES_BATCH_SIZE);
            foreach ($batches as $batch) {
                $this
                    ->getConnection()
                    ->update(
                        $tableName,
                        $preparedData,
                        $this->getConnection()->quoteInto($this->getIdFieldName() . ' IN (?)', $batch)
                    );
            }
        }
    }
}
