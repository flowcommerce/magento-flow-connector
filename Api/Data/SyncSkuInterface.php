<?php

namespace FlowCommerce\FlowConnector\Api\Data;

use \FlowCommerce\FlowConnector\Model\ResourceModel\SyncSku as SyncSkuResourceModel;

/**
 * Class SyncSkuInterface
 * @package FlowCommerce\FlowConnector\Api\Data
 */
interface SyncSkuInterface
{
    /**
     * Status - New
     */
    const STATUS_NEW = SyncSkuResourceModel::STATUS_NEW;

    /**
     * Status - Processing
     */
    const STATUS_PROCESSING = SyncSkuResourceModel::STATUS_PROCESSING;

    /**
     * Status - Error
     */
    const STATUS_ERROR = SyncSkuResourceModel::STATUS_ERROR;

    /**
     * Status - Done
     */
    const STATUS_DONE = SyncSkuResourceModel::STATUS_DONE;

    /**
     * Data Key - ID
     */
    const DATA_KEY_ID = 'id';

    /**
     * Data Key - Should sync children
     */
    const DATA_KEY_SHOULD_SYNC_CHILDREN = 'should_sync_children';

    /**
     * Data Key - Store ID
     */
    const DATA_KEY_STORE_ID = 'store_id';

    /**
     * Data Key - Sku
     */
    const DATA_KEY_SKU = 'sku';

    /**
     * Data Key - Priority
     */
    const DATA_KEY_PRIORITY = 'priority';

    /**
     * Data Key - Status
     */
    const DATA_KEY_STATUS = 'status';

    /**
     * Data Key - Message
     */
    const DATA_KEY_MESSAGE = 'message';

    /**
     * Data Key - Created At
     */
    const DATA_KEY_CREATED_AT = 'created_at';

    /**
     * Data Key - Updated At
     */
    const DATA_KEY_UPDATED_AT = 'updated_at';

    /**
     * Data Key - Deleted At
     */
    const DATA_KEY_DELETED_AT = 'deleted_at';

    /**
     * Getter - ID
     * @return int|null
     */
    public function getId();

    /**
     * Getter - Store ID
     * @return int|null
     */
    public function getStoreId();

    /**
     * Getter - SKU
     * @return string|null
     */
    public function getSku();

    /**
     * Getter - Is Should Sync Children
     * @return bool
     */
    public function isShouldSyncChildren();

    /**
     * Getter - Priority
     * @return int|null
     */
    public function getPriority();

    /**
     * Getter - Status
     * @return string|null
     */
    public function getStatus();

    /**
     * Getter - Message
     * @return string|null
     */
    public function getMessage();

    /**
     * Getter - Created At
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Getter - Updated At
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Getter - Deleted At
     * @return string|null
     */
    public function getDeletedAt();

    /**
     * Setter - ID
     * @param int $value
     * @return SyncSkuInterface
     */
    public function setId($value);

    /**
     * Setter - Store ID
     * @param int $value
     * @return SyncSkuInterface
     */
    public function setStoreId($value);

    /**
     * Setter - SKU
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setSku($value);

    /**
     * Setter - Is Should Sync Children
     * @param int $value
     * @return SyncSkuInterface
     */
    public function setShouldSyncChildren($value);

    /**
     * Setter - Priority
     * @param int $value
     * @return SyncSkuInterface
     */
    public function setPriority($value);

    /**
     * Setter - Status
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setStatus($value);

    /**
     * Setter - Message
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setMessage($value);

    /**
     * Setter - Created At
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setCreatedAt($value);

    /**
     * Setter - Updated At
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setUpdatedAt($value);

    /**
     * Setter - Deleted At
     * @param string $value
     * @return SyncSkuInterface
     */
    public function setDeletedAt($value);
}
