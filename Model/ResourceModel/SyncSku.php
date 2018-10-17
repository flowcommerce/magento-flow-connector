<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class SyncSku
 * @package FlowCommerce\FlowConnector\Model\ResourceModel
 */
class SyncSku extends AbstractDb
{
    /**
     * Possible values for status
     */
    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_ERROR = 'error';
    const STATUS_DONE = 'done';

    /**
     * Possible values for state
     */
    const STATE_NEW = 'new';
    const STATE_DONE = 'done';

    /**
     * Initializes resource model
     */
    protected function _construct()
    {
        $this->_init('flow_connector_sync_skus', 'id');
    }

    /**
     * Enqueues all products from given store ids
     * @param int[] @storeIds
     * @return void
     * @throws
     */
    public function enqueueAllProductsFromStores($storeIds)
    {
        if (count($storeIds) > 0) {
            $connection = $this->getConnection();
            $tableName = $this->getMainTable();
            $tableProduct = $this->getTable('catalog_product_entity');
            $tableProductWebsite = $this->getTable('catalog_product_website');
            $tableStore = $this->getTable('store');

            // Add any new SKUs from catalog_product_entity to flow_connector_sync_skus with state and status new.
            $sql = '
            insert into ' . $tableName . '(store_id, sku, status, state)
            select ' . $tableStore . '.store_id, ' . $tableProduct . '.sku, \'' . self::STATUS_NEW . '\', \'' . self::STATE_NEW . '\'
              from ' . $tableProduct . ',
                   ' . $tableProductWebsite . ',
                   ' . $tableStore . '
             where ' . $tableProduct . '.entity_id = ' . $tableProductWebsite . '.product_id
               and ' . $tableProductWebsite . '.website_id = ' . $tableStore . '.website_id
               and ' . $tableStore . '.store_id in (\'' . implode('\',\'', $storeIds) . '\')
               and ' . $tableStore . '.is_active = 1
             group by ' . $tableStore . '.store_id, sku
             on duplicate key update status=\''.self::STATUS_NEW.'\';
            ';
            $connection->query($sql);
        }
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
     * Reset any items that have been stuck processing for too long.
     * @return void
     * @throws
     */
    public function resetOldQueueProcessingItems()
    {
        $connection = $this->getConnection();
        $tableName = $this->getMainTable();
        $sql = '
        update ' . $tableName . '
           set status=\'' . self::STATUS_NEW . '\'
         where status=\'' . self::STATUS_PROCESSING . '\'
           and updated_at < date_sub(now(), interval 4 hour)
        ';
        $connection->query($sql);
    }

    /**
     * Updates given SyncSku model status and related columns in the database
     * This is a temporary workaround, as some clients were experiencing issues when
     * SyncSkus were beings saved using the model's save method
     * @param \FlowCommerce\FlowConnector\Api\Data\SyncSkuInterface $syncSku
     * @throws
     */
    public function updateStatus($syncSku)
    {
        $tableName = $this->getMainTable();
        $sql = 'update ' . $tableName . '
           set status = ?,
               state = ?,
               message = ?,
               request_url = ?,
               request_body = ?,
               response_headers = ?,
               response_body = ?
         where id = ?';
        $this->getConnection()->query($sql, [
            $syncSku->getStatus(),
            $syncSku->getState(),
            $syncSku->getMessage(),
            $syncSku->getRequestUrl(),
            $syncSku->getRequestBody(),
            $syncSku->getResponseHeaders(),
            $syncSku->getResponseBody(),
            $syncSku->getId()
        ]);
    }
}
