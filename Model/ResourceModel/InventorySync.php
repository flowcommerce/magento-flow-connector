<?php

namespace FlowCommerce\FlowConnector\Model\ResourceModel;

use FlowCommerce\FlowConnector\Api\Data\InventorySyncInterface;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class InventorySync
 * @package FlowCommerce\FlowConnector\Model\ResourceModel
 */
class InventorySync extends AbstractDb
{
    /**
     * Update multiple statuses batch size
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
     * Initialize resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('flow_connector_sync_inventory', InventorySyncInterface::DATA_KEY_ID);
    }

    /**
     * Deletes multiple inventory syncs using one single query to the database
     * @param InventorySyncInterface[] $inventorySyncs
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteMultiple(array $inventorySyncs)
    {
        $ids = [];
        foreach ($inventorySyncs as $inventorySync) {
            array_push($ids, $inventorySync->getId());
        }
        if (count($inventorySyncs)) {
            $connection = $this->getConnection();
            $this->objectRelationProcessor->delete(
                $this->transactionManager,
                $connection,
                $this->getMainTable(),
                $this->getConnection()->quoteInto($this->getIdFieldName() . 'IN ?', $ids),
                []
            );
        }
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
        $productIdField = InventorySyncInterface::DATA_KEY_PRODUCT_ID;
        $statusField = InventorySyncInterface::DATA_KEY_STATUS;
        $updatedAtField = InventorySyncInterface::DATA_KEY_UPDATED_AT;
        $sql = '
            delete s1
              from ' . $tableName . ' s1
              join ' . $tableName . ' s2
                on s1.' . $productIdField . ' = s2.' . $productIdField . '
               and s1.' . $statusField . ' = \'' . InventorySyncInterface::STATUS_ERROR . '\'
               and s2.' . $statusField . ' = \'' . InventorySyncInterface::STATUS_DONE . '\'
               and s1.' . $updatedAtField . ' < s2.' . $updatedAtField . '
            ';
        $connection->query($sql);
    }

    /**
     * Saves multiple inventory syncs efficiently querying the database
     * @param InventorySyncInterface[] $inventorySyncs
     * @throws LocalizedException
     */
    public function saveMultiple(array $inventorySyncs)
    {
        $preparedData = [];
        foreach ($inventorySyncs as $inventorySync) {
            $inventorySyncData = [];
            foreach ($inventorySync->getData() as $key => $value) {
                $inventorySyncData[$key] = $value;
            }
            array_push($preparedData, $inventorySyncData);
        }
        if (count($inventorySyncs)) {
            $tableName = $this->getMainTable();

            $this
                ->getConnection()
                ->insertMultiple(
                    $tableName,
                    $preparedData
                );
        }
    }

    /**
     * Updates status for multiple inventory syncs using one single query to the database
     * @param InventorySyncInterface[] $inventorySyncs
     * @param string $newStatus
     * @throws LocalizedException
     */
    public function updateMultipleStatuses(array $inventorySyncs, $newStatus)
    {
        $ids = [];
        foreach ($inventorySyncs as $inventorySync) {
            array_push($ids, $inventorySync->getId());
        }
        if (count($inventorySyncs)) {
            $tableName = $this->getMainTable();

            $object = $this->dataObjectFactory->create();
            $object->setData(InventorySyncInterface::DATA_KEY_STATUS, $newStatus);
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
