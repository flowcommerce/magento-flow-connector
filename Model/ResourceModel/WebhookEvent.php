<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\Data\WebhookEventInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Exception\LocalizedException;

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
            store_id,
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
            store_id = ?,
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
                $webhookEvent->getStoreId(),
                $webhookEvent->getTriggeredAt(),
                $webhookEvent->getStatus(),
                $webhookEvent->getMessage(),
                $webhookEvent->getType(),
                $webhookEvent->getPayload(),
                $webhookEvent->getStoreId(),
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
}
