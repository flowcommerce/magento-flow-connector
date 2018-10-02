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
     * Initializes resource model
     */
    protected function _construct()
    {
        $this->_init('flow_connector_sync_skus', 'id');
    }

    /**
     * Deletes old processed items.
     * @return void
     * @throws
     */
    public function deleteOldQueueDoneItems()
    {
        $connection = $this->getConnection();
        $tableName = $this->getMainTable();
        $sql = '
        delete from ' . $tableName . '
         where status=\'' . self::STATUS_DONE . '\'
           and updated_at < date_sub(now(), interval 96 hour)
        ';
        $connection->query($sql);
    }

    /**
     * Deletes items with errors where there is a new record that is done.
     * @return void
     * @throws
     */
    public function deleteQueueErrorDoneItems()
    {
        $connection = $this->getConnection();
        $tableName = $this->getMainTable();
        $sql = '
        delete s1
          from ' . $tableName . ' s1
          join ' . $tableName . ' s2
            on s1.sku = s2.sku
           and s1.status = \'' . self::STATUS_ERROR . '\'
           and s2.status = \'' . self::STATUS_DONE . '\'
           and s1.updated_at < s2.updated_at
        ';
        $connection->query($sql);
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

            // Truncate flow_connector_sync_skus before enqueuing all product for performance reasons
            $connection->truncateTable($tableName);

            $sql = '
            insert into ' . $tableName . '(store_id, sku, status)
            select ' . $tableStore . '.store_id, ' . $tableProduct . '.sku, \'' . self::STATUS_NEW . '\'
              from ' . $tableProduct . ',
                   ' . $tableProductWebsite . ',
                   ' . $tableStore . '
             where ' . $tableProduct . '.entity_id = ' . $tableProductWebsite . '.product_id
               and ' . $tableProductWebsite . '.website_id = ' . $tableStore . '.website_id
               and ' . $tableStore . '.store_id in (\'' . implode('\',\'', $storeIds) . '\')
               and ' . $tableStore . '.is_active = 1
               and not exists (
                     select 1
                       from ' . $tableName . '
                      where ' . $tableName . '.sku = ' . $tableProduct . '.sku
                        and ' . $tableName . '.status = \'' . self::STATUS_NEW . '\'
                   )
             group by ' . $tableStore . '.store_id, sku
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
     * Updates given SyncSku model data in the database
     * This is a temporary workaround, as some clients were experiencing issues when
     * SyncSkus were beings saved using the model's save method
     * @param $syncSku
     * @throws
     */
    public function update($syncSku)
    {
        $tableName = $this->getMainTable();
        $sql = '
        update ' . $tableName . '
           set status = ?,
               message = ?
         where id = ?
        ';
        $this->getConnection()->query($sql, [$syncSku->getStatus(), $syncSku->getMessage(), $syncSku->getId()]);
    }
}
